<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\TestCase;

class FakeStripeHttpClient implements ClientInterface
{
    private array $responses;
    private int $callIndex = 0;

    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
    {
        $response = $this->responses[$this->callIndex] ?? end($this->responses);
        $this->callIndex++;
        return [json_encode($response), 200, ['Request-Id' => 'req_test']];
    }
}

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.secret' => 'sk_test_' . str_repeat('x', 24)]);
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);
        parent::tearDown();
    }

    private function setFakeStripe(): void
    {
        ApiRequestor::setHttpClient(new FakeStripeHttpClient([
            [
                'id'             => 'cs_test_fake',
                'object'         => 'checkout.session',
                'url'            => 'https://checkout.stripe.com/pay/fake',
                'payment_status' => 'unpaid',
                'livemode'       => false,
            ],
            [
                'id'             => 'cs_test_fake',
                'object'         => 'checkout.session',
                'url'            => 'https://checkout.stripe.com/pay/fake',
                'payment_status' => 'paid',
                'livemode'       => false,
            ],
        ]));
    }

    private function completePurchase(User $buyer, Item $item): void
    {
        $this->setFakeStripe();

        // 「購入する」ボタン押下 → Stripe へリダイレクト
        $this->actingAs($buyer)
            ->post(route('purchase.store', $item), [
                'payment_method' => 'カード支払い',
            ]);

        // Stripe 決済完了後のコールバックを模擬
        $this->actingAs($buyer)
            ->withSession([
                'purchase_stripe_data' => [
                    'item_id'        => $item->id,
                    'payment_method' => 'カード支払い',
                    'postal_code'    => $buyer->postal_code,
                    'address'        => $buyer->address,
                    'building'       => $buyer->building,
                ],
            ])
            ->get(route('purchase.success', $item) . '?session_id=cs_test_fake');
    }

    /** @test */
    public function selected_payment_method_is_reflected_in_summary()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $item   = Item::factory()->create(['user_id' => $seller->id]);

        $response = $this->actingAs($buyer)
            ->get(route('purchase.show', $item));

        $response->assertStatus(200);

        // プルダウンに両方の選択肢が存在する
        $response->assertSee('コンビニ支払い');
        $response->assertSee('カード支払い');

        // 小計欄に支払い方法の表示エリアが存在する
        $response->assertSee('id="summary_payment"', false);

        // JavaScript が選択変更を小計に反映するコードを含む
        $response->assertSee('summaryPayment.textContent = this.value', false);
    }

    /** @test */
    public function purchase_is_completed_when_buy_button_is_pressed()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $item   = Item::factory()->create(['user_id' => $seller->id]);

        $this->setFakeStripe();

        // 「購入する」ボタン押下
        $this->actingAs($buyer)
            ->post(route('purchase.store', $item), [
                'payment_method' => 'カード支払い',
            ])
            ->assertRedirect('https://checkout.stripe.com/pay/fake');

        // Stripe 決済完了後のコールバックを模擬
        $this->actingAs($buyer)
            ->withSession([
                'purchase_stripe_data' => [
                    'item_id'        => $item->id,
                    'payment_method' => 'カード支払い',
                    'postal_code'    => $buyer->postal_code,
                    'address'        => $buyer->address,
                    'building'       => $buyer->building,
                ],
            ])
            ->get(route('purchase.success', $item) . '?session_id=cs_test_fake')
            ->assertRedirect(route('items.index'));

        $this->assertDatabaseHas('purchases', [
            'user_id' => $buyer->id,
            'item_id' => $item->id,
        ]);
    }

    /** @test */
    public function purchased_item_shows_sold_label_in_item_list()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $item   = Item::factory()->create(['user_id' => $seller->id]);

        $this->completePurchase($buyer, $item);

        $response = $this->actingAs($buyer)->get(route('items.index'));

        $response->assertStatus(200);
        $response->assertSee('Sold');
    }

    /** @test */
    public function purchased_item_appears_in_profile_purchase_list()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $item   = Item::factory()->create(['user_id' => $seller->id]);

        $this->completePurchase($buyer, $item);

        $response = $this->actingAs($buyer)->get('/mypage?tab=buy');

        $response->assertStatus(200);
        $response->assertSee($item->name);
    }
}
