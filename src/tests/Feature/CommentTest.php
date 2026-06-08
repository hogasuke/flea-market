<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_post_a_comment()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $initialCount = $item->comments()->count();

        $response = $this->actingAs($user)
            ->post(route('items.comments.store', $item->id), [
                'content' => 'テストコメントです',
            ]);

        $response->assertRedirect(route('items.show', $item->id));

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'content' => 'テストコメントです',
        ]);

        $this->assertEquals($initialCount + 1, $item->comments()->count());
    }

    /** @test */
    public function unauthenticated_user_cannot_post_a_comment()
    {
        $item = Item::factory()->create();

        $response = $this->post(route('items.comments.store', $item->id), [
            'content' => 'テストコメントです',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('comments', [
            'item_id' => $item->id,
            'content' => 'テストコメントです',
        ]);
    }

    /** @test */
    public function empty_comment_shows_validation_message()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('items.comments.store', $item->id), [
                'content' => '',
            ]);

        $response->assertSessionHasErrors(['content' => 'コメントを入力してください']);
    }

    /** @test */
    public function comment_exceeding_255_characters_shows_validation_message()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('items.comments.store', $item->id), [
                'content' => str_repeat('あ', 256),
            ]);

        $response->assertSessionHasErrors(['content' => 'コメントは255文字以内で入力してください']);
    }
}
