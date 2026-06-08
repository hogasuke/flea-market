<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Item;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemDetailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function item_detail_page_displays_all_required_information()
    {
        $seller = User::factory()->create(['name' => '出品者テスト']);

        $item = Item::factory()->create([
            'user_id'     => $seller->id,
            'name'        => 'テスト商品名',
            'brand_name'  => 'テストブランド',
            'description' => 'これはテスト用の商品説明です。',
            'price'       => 3000,
            'image_path'  => 'storage/items/dummy.jpg',
            'condition'   => '良好',
        ]);

        $category = Category::factory()->create(['name' => 'テストカテゴリ']);
        $item->categories()->attach($category->id);

        $commenter = User::factory()->create(['name' => 'コメントユーザー']);
        Comment::create([
            'user_id' => $commenter->id,
            'item_id' => $item->id,
            'content' => 'これはテストコメントです。',
        ]);

        Like::create(['user_id' => User::factory()->create()->id, 'item_id' => $item->id]);
        Like::create(['user_id' => User::factory()->create()->id, 'item_id' => $item->id]);

        $response = $this->get(route('items.show', $item->id));

        $response->assertStatus(200);

        // 商品画像
        $response->assertSee('storage/items/dummy.jpg');

        // 商品名
        $response->assertSee('テスト商品名');

        // ブランド名
        $response->assertSee('テストブランド');

        // 価格（number_format で ¥3,000 と表示される）
        $response->assertSee('¥3,000');
        $response->assertSee('税込');

        // いいね数
        $response->assertSee('2');

        // コメント数（ヘッダーとセクション見出し両方に表示）
        $response->assertSee('コメント(1)');

        // 商品説明
        $response->assertSee('これはテスト用の商品説明です。');

        // カテゴリ
        $response->assertSee('テストカテゴリ');

        // 商品の状態
        $response->assertSee('良好');

        // コメントしたユーザー情報
        $response->assertSee('コメントユーザー');

        // コメント内容
        $response->assertSee('これはテストコメントです。');
    }

    /** @test */
    public function item_detail_page_displays_multiple_selected_categories()
    {
        $seller = User::factory()->create();

        $item = Item::factory()->create(['user_id' => $seller->id]);

        $category1 = Category::factory()->create(['name' => 'ファッション']);
        $category2 = Category::factory()->create(['name' => 'スポーツ']);
        $category3 = Category::factory()->create(['name' => '家電']);

        $item->categories()->attach([$category1->id, $category2->id, $category3->id]);

        $response = $this->get(route('items.show', $item->id));

        $response->assertStatus(200);
        $response->assertSee('ファッション');
        $response->assertSee('スポーツ');
        $response->assertSee('家電');
    }
}
