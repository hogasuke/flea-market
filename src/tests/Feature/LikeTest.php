<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_like_an_item()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $response = $this->actingAs($user)
            ->post(route('items.like', $item->id));

        $response->assertRedirect();

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);
    }

    /** @test */
    public function like_count_increases_after_liking()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $initialCount = $item->likes()->count();

        $this->actingAs($user)
            ->post(route('items.like', $item->id));

        $this->assertEquals($initialCount + 1, $item->likes()->count());
    }

    /** @test */
    public function liked_item_shows_filled_heart_icon_and_liked_color()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        Like::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('items.show', $item->id));

        $response->assertStatus(200);
        $response->assertSee('♥');
        $response->assertSee('item-detail__meta-icon--liked');
    }

    /** @test */
    public function authenticated_user_can_unlike_an_already_liked_item()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        Like::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $response = $this->actingAs($user)
            ->post(route('items.like', $item->id));

        $response->assertRedirect();

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);
    }

    /** @test */
    public function like_count_decreases_after_unliking()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        Like::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $countBeforeUnlike = $item->likes()->count();

        $this->actingAs($user)
            ->post(route('items.like', $item->id));

        $this->assertEquals($countBeforeUnlike - 1, $item->likes()->count());
    }
}
