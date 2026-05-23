<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExhibitionRequest;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    public function index()
    {
        $keyword = trim((string) request('keyword'));
        $tab = request('tab', 'recommend');

        $purchasedItemIds = auth()->check()
            ? auth()->user()->purchases()->pluck('item_id')->toArray()
            : [];

        if ($tab === 'mylist') {
            if (!auth()->check()) {
                return redirect()->route('login');
            }

            $items = Item::query()
                ->whereHas('likes', fn($q) => $q->where('user_id', auth()->id()))
                ->when($keyword !== '', fn($q) => $q->where('name', 'like', '%' . $keyword . '%'))
                ->latest()
                ->get();

            return view('items.index', compact('items', 'tab', 'purchasedItemIds'));
        }

        $itemsQuery = Item::query()
            ->when(auth()->check(), fn($q) => $q->where('user_id', '!=', auth()->id()))
            ->latest();

        if ($keyword !== '') {
            $itemsQuery->where('name', 'like', '%' . $keyword . '%');
        }

        $items = $itemsQuery->get();

        return view('items.index', compact('items', 'tab', 'purchasedItemIds'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('items.exhibition', compact('categories'));
    }

    public function store(ExhibitionRequest $request)
    {
        $path = $request->file('image')->store('items', 'public');

        $item = Item::create([
            'user_id'     => Auth::id(),
            'name'        => $request->name,
            'brand_name'  => $request->brand_name,
            'description' => $request->description,
            'price'       => $request->price,
            'image_path'  => 'storage/' . $path,
            'condition'   => $request->condition,
        ]);

        $item->categories()->attach($request->categories);

        return redirect('/');
    }

    public function show(Item $item)
    {
        $item->load([
            'categories',
            'comments.user',
        ])->loadCount([
            'likes',
            'comments',
        ]);

        $isLiked = auth()->check()
            ? $item->likes()->where('user_id', auth()->id())->exists()
            : false;

        return view('items.show', compact('item', 'isLiked'));
    }
}
