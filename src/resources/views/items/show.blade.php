@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/show.css') }}">
@endsection

@section('content')
    <div class="item-detail">
        <div class="item-detail__image">
            <img src="{{ asset($item->image_path) }}" alt="{{ $item->name }}">
        </div>

        <div class="item-detail__content">
            <section class="item-detail__section item-detail__section--head">
                <h1 class="item-detail__name">{{ $item->name }}</h1>
                <p class="item-detail__brand">{{ $item->brand_name ?: 'ブランド名なし' }}</p>
                <p class="item-detail__price">¥{{ number_format($item->price) }}<span>(税込)</span></p>

                <div class="item-detail__meta">
                    @auth
                        <form action="{{ route('items.like', $item->id) }}" method="POST" class="item-detail__like-form">
                            @csrf
                            <button type="submit" class="item-detail__meta-item item-detail__like-button">
                                <span class="item-detail__meta-icon {{ $isLiked ? 'item-detail__meta-icon--liked' : '' }}">
                                    {{ $isLiked ? '♥' : '♡' }}
                                </span>
                                <span class="item-detail__meta-count">{{ $item->likes_count }}</span>
                            </button>
                        </form>
                    @else
                        <div class="item-detail__meta-item">
                            <span class="item-detail__meta-icon">♡</span>
                            <span class="item-detail__meta-count">{{ $item->likes_count }}</span>
                        </div>
                    @endauth
                    <div class="item-detail__meta-item">
                        <span class="item-detail__meta-icon">◯</span>
                        <span class="item-detail__meta-count">{{ $item->comments_count }}</span>
                    </div>
                </div>

                @auth
                    <a class="item-detail__purchase-button" href="{{ route('purchase.show', $item->id) }}">購入手続きへ</a>
                @else
                    <a class="item-detail__purchase-button" href="{{ route('login') }}">購入手続きへ</a>
                @endauth
            </section>

            <section class="item-detail__section">
                <h2 class="item-detail__heading">商品説明</h2>

                <div class="item-detail__description">
                    <p>{!! nl2br(e($item->description)) !!}</p>
                </div>
            </section>

            <section class="item-detail__section">
                <h2 class="item-detail__heading">商品の情報</h2>

                <dl class="item-detail__info">
                    <div class="item-detail__info-row">
                        <dt>カテゴリー</dt>
                        <dd>
                            @forelse ($item->categories as $category)
                                <span class="item-detail__tag">{{ $category->name }}</span>
                            @empty
                                <span>未設定</span>
                            @endforelse
                        </dd>
                    </div>
                    <div class="item-detail__info-row">
                        <dt>商品の状態</dt>
                        <dd>{{ $item->condition }}</dd>
                    </div>
                </dl>
            </section>

            <section class="item-detail__section">
                <h2 class="item-detail__heading item-detail__heading--comment">コメント({{ $item->comments_count }})</h2>

                @forelse ($item->comments as $comment)
                    <div class="item-detail__comment-user">
                        <span class="item-detail__comment-avatar"></span>
                        <span class="item-detail__comment-name">{{ optional($comment->user)->name ?? '退会ユーザー' }}</span>
                    </div>
                    <div class="item-detail__comment-body">
                        {{ $comment->content }}
                    </div>
                @empty
                    <div class="item-detail__comment-body">
                        コメントはまだありません。
                    </div>
                @endforelse
            </section>

            @auth
                <section class="item-detail__section">
                    <h2 class="item-detail__comment-title">商品へのコメント</h2>
                    <form action="{{ route('items.comments.store', $item->id) }}" method="POST">
                        @csrf
                        <textarea class="item-detail__comment-input" name="content">{{ old('content') }}</textarea>
                        @if ($errors->has('content'))
                            <div class="item-detail__comment-error">
                                {{ $errors->first('content') }}
                            </div>
                        @endif
                        <button class="item-detail__comment-button" type="submit">コメントを送信する</button>
                    </form>
                </section>
            @endauth
        </div>
    </div>
@endsection
