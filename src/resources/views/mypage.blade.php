@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
@endsection

@section('content')
    <div class="mypage">
        <div class="mypage__profile">
            <div class="mypage__avatar">
                @if ($user->profile_image)
                    <img src="{{ asset('storage/' . $user->profile_image) }}" alt="{{ $user->name }}">
                @endif
            </div>
            <p class="mypage__username">{{ $user->name }}</p>
            <a class="mypage__edit-button" href="{{ route('mypage.profile') }}">プロフィールを編集</a>
        </div>

        <div class="item-index">
            <div class="item-index__tabs">
                <a class="item-index__tab {{ $tab !== 'buy' ? 'item-index__tab--active' : '' }}"
                    href="/mypage">出品した商品</a>
                <a class="item-index__tab {{ $tab === 'buy' ? 'item-index__tab--active' : '' }}"
                    href="/mypage?tab=buy">購入した商品</a>
            </div>

            <div class="item-grid">
                @if ($tab === 'buy')
                    @foreach ($purchasedItems as $item)
                        <a class="item-card" href="{{ route('items.show', $item->id) }}">
                            <article>
                                <div class="item-card__image">
                                    <img src="{{ asset($item->image_path) }}" alt="{{ $item->name }}">
                                </div>
                                <p class="item-card__name">{{ $item->name }}</p>
                            </article>
                        </a>
                    @endforeach
                @else
                    @foreach ($soldItems as $item)
                        <a class="item-card" href="{{ route('items.show', $item->id) }}">
                            <article>
                                <div class="item-card__image">
                                    <img src="{{ asset($item->image_path) }}" alt="{{ $item->name }}">
                                </div>
                                <p class="item-card__name">{{ $item->name }}</p>
                            </article>
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endsection
