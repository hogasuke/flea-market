<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MypageTest extends TestCase
{
    use RefreshDatabase;

    // --- ユーザー情報取得機能のテスト ---

    /** @test */
    public function mypage_displays_profile_image_username_sold_items_and_purchased_items()
    {
        $user  = User::factory()->create([
            'name'          => 'テストユーザー',
            'profile_image' => 'profile_images/test_avatar.jpg',
        ]);
        $other = User::factory()->create();

        $soldItem1 = Item::factory()->create(['user_id' => $user->id, 'name' => '出品商品A']);
        $soldItem2 = Item::factory()->create(['user_id' => $user->id, 'name' => '出品商品B']);

        $boughtItem = Item::factory()->create(['user_id' => $other->id, 'name' => '購入商品X']);
        Purchase::create([
            'user_id'        => $user->id,
            'item_id'        => $boughtItem->id,
            'payment_method' => 'card',
            'postal_code'    => '123-4567',
            'address'        => '東京都渋谷区1-1',
            'building'       => null,
        ]);

        $response = $this->actingAs($user)->get('/mypage');

        $response->assertStatus(200);
        $response->assertSee('profile_images/test_avatar.jpg', false);
        $response->assertSee('テストユーザー');
        $response->assertSee('出品商品A');
        $response->assertSee('出品商品B');
    }

    /** @test */
    public function mypage_buy_tab_displays_purchased_items()
    {
        $user  = User::factory()->create(['name' => 'テストユーザー']);
        $other = User::factory()->create();

        $boughtItem = Item::factory()->create(['user_id' => $other->id, 'name' => '購入商品X']);
        Purchase::create([
            'user_id'        => $user->id,
            'item_id'        => $boughtItem->id,
            'payment_method' => 'card',
            'postal_code'    => '123-4567',
            'address'        => '東京都渋谷区1-1',
            'building'       => null,
        ]);

        $response = $this->actingAs($user)->get('/mypage?tab=buy');

        $response->assertStatus(200);
        $response->assertSee('購入商品X');
    }

    // --- 変更項目の初期値表示テスト ---

    /** @test */
    public function profile_edit_form_shows_initial_values_for_all_fields()
    {
        $user = User::factory()->create([
            'name'          => '初期ユーザー名',
            'profile_image' => 'profile_images/avatar.jpg',
            'postal_code'   => '100-0001',
            'address'       => '東京都千代田区千代田1-1',
            'building'      => 'テストビル101',
        ]);

        $response = $this->actingAs($user)->get('/mypage/profile');

        $response->assertStatus(200);
        $response->assertSee('profile_images/avatar.jpg', false);
        $response->assertSee('value="初期ユーザー名"', false);
        $response->assertSee('value="100-0001"', false);
        $response->assertSee('value="東京都千代田区千代田1-1"', false);
    }
}
