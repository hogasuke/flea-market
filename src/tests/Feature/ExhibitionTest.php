<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExhibitionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_save_exhibition_with_all_fields()
    {
        Storage::fake('public');

        $user       = User::factory()->create();
        $category1  = Category::factory()->create(['name' => 'ファッション']);
        $category2  = Category::factory()->create(['name' => '家電・スマホ・カメラ']);

        $imagePath = base_path('public/images/icon/ふきだしロゴ.png');
        $image = new \Illuminate\Http\UploadedFile($imagePath, 'test.png', 'image/png', null, true);

        $response = $this->actingAs($user)->post(route('items.store'), [
            'image'       => $image,
            'categories'  => [$category1->id, $category2->id],
            'condition'   => '良好',
            'name'        => 'テスト商品名',
            'brand_name'  => 'テストブランド',
            'description' => 'これはテスト用の商品説明です。',
            'price'       => 3000,
        ]);

        $response->assertRedirect('/');

        $this->assertDatabaseHas('items', [
            'user_id'     => $user->id,
            'name'        => 'テスト商品名',
            'brand_name'  => 'テストブランド',
            'description' => 'これはテスト用の商品説明です。',
            'price'       => 3000,
            'condition'   => '良好',
        ]);

        $item = $user->fresh()->items()->first();

        $this->assertDatabaseHas('item_category', [
            'item_id'     => $item->id,
            'category_id' => $category1->id,
        ]);
        $this->assertDatabaseHas('item_category', [
            'item_id'     => $item->id,
            'category_id' => $category2->id,
        ]);
    }
}
