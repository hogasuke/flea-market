<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class PurchaseController extends Controller
{
    public function show(Item $item)
    {
        $user = auth()->user();
        $address = session('purchase_address', [
            'postal_code' => $user->postal_code,
            'address'     => $user->address,
            'building'    => $user->building,
        ]);

        return view('items.purchase', compact('item', 'user', 'address'));
    }

    public function store(PurchaseRequest $request, Item $item): RedirectResponse
    {
        $user = auth()->user();
        $address = session('purchase_address', [
            'postal_code' => $user->postal_code,
            'address'     => $user->address,
            'building'    => $user->building,
        ]);

        session(['purchase_stripe_data' => [
            'item_id'        => $item->id,
            'payment_method' => $request->input('payment_method'),
            'postal_code'    => $address['postal_code'],
            'address'        => $address['address'],
            'building'       => $address['building'] ?? null,
        ]]);

        Stripe::setApiKey(config('services.stripe.secret'));

        $paymentMethodTypes = $request->input('payment_method') === 'カード支払い'
            ? ['card']
            : ['konbini'];

        $checkoutSession = StripeSession::create([
            'payment_method_types' => $paymentMethodTypes,
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'jpy',
                    'product_data' => [
                        'name' => $item->name,
                    ],
                    'unit_amount'  => $item->price,
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => route('purchase.success', $item) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('purchase.show', $item),
            'payment_method_options' => $request->input('payment_method') === 'コンビニ支払い'
                ? ['konbini' => ['expires_after_days' => 3]]
                : [],
        ]);

        return redirect($checkoutSession->url);
    }

    public function success(Item $item): RedirectResponse
    {
        $data = session('purchase_stripe_data');

        if (!$data || $data['item_id'] !== $item->id) {
            return redirect()->route('items.index');
        }

        $sessionId = request()->query('session_id');
        Stripe::setApiKey(config('services.stripe.secret'));
        $stripeSession = StripeSession::retrieve($sessionId);

        if ($stripeSession->payment_status !== 'paid' && $stripeSession->payment_status !== 'no_payment_required') {
            return redirect()->route('purchase.show', $item);
        }

        Purchase::create([
            'user_id'        => auth()->id(),
            'item_id'        => $item->id,
            'payment_method' => $data['payment_method'],
            'postal_code'    => $data['postal_code'],
            'address'        => $data['address'],
            'building'       => $data['building'],
        ]);

        session()->forget(['purchase_stripe_data', 'purchase_address']);

        return redirect()->route('items.index');
    }
}
