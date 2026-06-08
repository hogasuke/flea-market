<?php

namespace Tests\Feature;

use App\Models\Item;
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
}
