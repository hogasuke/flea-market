<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\ApiRequestor;
use Tests\TestCase;

class AddressTest extends TestCase
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
            // store() → StripeSession::create()
            [
                'id'             => 'cs_test_fake',
                'object'         => 'checkout.session',
                'url'            => 'https://checkout.stripe.com/pay/fake',
                'payment_status' => 'unpaid',
                'livemode'       => false,
            ],
            // success() → StripeSession::retrieve()
            [
                'id'             => 'cs_test_fake',
                'object'         => 'checkout.session',
                'url'            => 'https://checkout.stripe.com/pay/fake',
                'payment_status' => 'paid',
                'livemode'       => false,
            ],
        ]));
    }

    /** @test */
    public function address_change_is_reflected_on_purchase_page(): void
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create([
            'postal_code' => '000-0000',
            'address'     => '変更前の旧住所',
            'building'    => null,
        ]);
        $item = Item::factory()->create(['user_id' => $seller->id]);

        $newAddress = [
            'postal_code' => '100-0001',
            'address'     => '東京都千代田区丸の内1-1',
            'building'    => 'テストビル101',
        ];

        // 送付先住所変更画面で住所を登録する
        $this->actingAs($buyer)
            ->post(route('purchase.address', $item), $newAddress)
            ->assertRedirect(route('purchase.show', $item));

        // 商品購入画面を開く
        $response = $this->actingAs($buyer)
            ->get(route('purchase.show', $item));

        // 登録した住所が反映されている
        $response->assertStatus(200);
        $response->assertSee($newAddress['postal_code']);
        $response->assertSee($newAddress['address']);
    }

    /** @test */
    public function purchased_item_has_correct_delivery_address(): void
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create([
            'postal_code' => '000-0000',
            'address'     => '変更前の旧住所',
            'building'    => null,
        ]);
        $item = Item::factory()->create(['user_id' => $seller->id]);

        $newAddress = [
            'postal_code' => '100-0001',
            'address'     => '東京都千代田区丸の内1-1',
            'building'    => 'テストビル101',
        ];

        // 送付先住所変更画面で住所を登録する
        $this->actingAs($buyer)
            ->post(route('purchase.address', $item), $newAddress);

        // 商品を購入する
        $this->setFakeStripe();
        $this->actingAs($buyer)
            ->post(route('purchase.store', $item), ['payment_method' => 'カード支払い']);

        // Stripe 決済完了後のコールバックを模擬
        $this->actingAs($buyer)
            ->get(route('purchase.success', $item) . '?session_id=cs_test_fake')
            ->assertRedirect(route('items.index'));

        // 変更した住所が購入レコードに紐づいている
        $this->assertDatabaseHas('purchases', [
            'user_id'     => $buyer->id,
            'item_id'     => $item->id,
            'postal_code' => $newAddress['postal_code'],
            'address'     => $newAddress['address'],
            'building'    => $newAddress['building'],
        ]);
    }
}
