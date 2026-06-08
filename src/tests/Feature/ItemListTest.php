<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Like;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function all_items_are_displayed()
    {
        $items = Item::factory()->count(3)->create();

        $response = $this->get('/');

        $response->assertStatus(200);
        foreach ($items as $item) {
            $response->assertSee($item->name);
        }
    }

    /** @test */
    public function purchased_item_shows_sold_label()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();

        $item = Item::factory()->create(['user_id' => $seller->id]);

        Purchase::create([
            'user_id'        => $buyer->id,
            'item_id'        => $item->id,
            'payment_method' => 'card',
            'postal_code'    => '123-4567',
            'address'        => '東京都渋谷区1-1',
            'building'       => null,
        ]);

        $response = $this->actingAs($buyer)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Sold');
    }

    /** @test */
    public function own_listed_items_are_not_displayed()
    {
        $loginUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownItem   = Item::factory()->create(['user_id' => $loginUser->id]);
        $otherItem = Item::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($loginUser)->get('/');

        $response->assertStatus(200);
        $response->assertDontSee($ownItem->name);
        $response->assertSee($otherItem->name);
    }

    // --- マイリストのテスト ---

    /** @test */
    public function only_liked_items_are_displayed_in_mylist()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $likedItem1  = Item::factory()->create(['user_id' => $other->id]);
        $likedItem2  = Item::factory()->create(['user_id' => $other->id]);
        $unlikedItem = Item::factory()->create(['user_id' => $other->id]);

        Like::create(['user_id' => $user->id, 'item_id' => $likedItem1->id]);
        Like::create(['user_id' => $user->id, 'item_id' => $likedItem2->id]);

        $response = $this->actingAs($user)->get('/?tab=mylist');

        $response->assertStatus(200);
        $response->assertSee($likedItem1->name);
        $response->assertSee($likedItem2->name);
        $response->assertDontSee($unlikedItem->name);
    }

    /** @test */
    public function purchased_item_shows_sold_label_in_mylist()
    {
        $buyer  = User::factory()->create();
        $seller = User::factory()->create();

        $item = Item::factory()->create(['user_id' => $seller->id]);

        Like::create(['user_id' => $buyer->id, 'item_id' => $item->id]);

        Purchase::create([
            'user_id'        => $buyer->id,
            'item_id'        => $item->id,
            'payment_method' => 'card',
            'postal_code'    => '123-4567',
            'address'        => '東京都渋谷区1-1',
            'building'       => null,
        ]);

        $response = $this->actingAs($buyer)->get('/?tab=mylist');

        $response->assertStatus(200);
        $response->assertSee('Sold');
    }

    /** @test */
    public function unauthenticated_user_is_redirected_from_mylist()
    {
        Item::factory()->count(3)->create();

        $response = $this->get('/?tab=mylist');

        $response->assertRedirect(route('login'));
    }

    // --- 商品検索のテスト ---

    /** @test */
    public function search_by_partial_name_shows_matching_items()
    {
        $owner = User::factory()->create();

        $matchingItem    = Item::factory()->create(['user_id' => $owner->id, 'name' => 'テスト商品']);
        $nonMatchingItem = Item::factory()->create(['user_id' => $owner->id, 'name' => '別の商品']);

        $response = $this->get('/?keyword=テスト');

        $response->assertStatus(200);
        $response->assertSee($matchingItem->name);
        $response->assertDontSee($nonMatchingItem->name);
    }

    /** @test */
    public function search_keyword_is_preserved_on_mylist_tab()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $keyword   = 'テスト';
        $likedItem = Item::factory()->create(['user_id' => $other->id, 'name' => 'テスト商品']);
        Like::create(['user_id' => $user->id, 'item_id' => $likedItem->id]);

        $this->actingAs($user)->get('/?keyword=' . $keyword)->assertStatus(200);

        $mylistResponse = $this->actingAs($user)->get('/?tab=mylist&keyword=' . $keyword);

        $mylistResponse->assertStatus(200);
        $mylistResponse->assertSee('value="' . $keyword . '"', false);
    }
}
