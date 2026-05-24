@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/exhibition.css') }}">
@endsection

@section('content')
    <div class="sell">
        <h1 class="sell__title">商品の出品</h1>

        <form class="sell-form" action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf

            {{-- 商品画像 --}}
            <div class="sell-form__section">
                <h2 class="sell-form__section-title">商品画像</h2>
                <div class="sell-form__image-area" id="imageArea">
                    <img class="sell-form__image-preview" id="imagePreview" src="" alt="" style="display:none;">
                    <label class="sell-form__image-button" for="image">画像を選択する</label>
                    <input class="sell-form__image-input" type="file" id="image" name="image" accept="image/*">
                </div>
                @error('image')
                    <p class="sell-form__error">{{ $message }}</p>
                @enderror
            </div>

            {{-- 商品の詳細 --}}
            <div class="sell-form__section sell-form__section--bordered">
                <h2 class="sell-form__section-title">商品の詳細</h2>

                <div class="sell-form__field">
                    <label class="sell-form__label">カテゴリー</label>
                    <div class="sell-form__tags">
                        @foreach ($categories as $category)
                            <label class="sell-form__tag {{ in_array($category->id, old('categories', [])) ? 'sell-form__tag--selected' : '' }}">
                                <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                                    {{ in_array($category->id, old('categories', [])) ? 'checked' : '' }}>
                                {{ $category->name }}
                            </label>
                        @endforeach
                    </div>
                    @error('categories')
                        <p class="sell-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sell-form__field">
                    <label class="sell-form__label" for="condition">商品の状態</label>
                    <div class="sell-form__select-wrapper">
                        <select class="sell-form__select" id="condition" name="condition">
                            <option value="" disabled {{ old('condition') ? '' : 'selected' }}>選択してください</option>
                            <option value="良好" {{ old('condition') === '良好' ? 'selected' : '' }}>良好</option>
                            <option value="目立った傷や汚れなし" {{ old('condition') === '目立った傷や汚れなし' ? 'selected' : '' }}>目立った傷や汚れなし</option>
                            <option value="やや傷や汚れあり" {{ old('condition') === 'やや傷や汚れあり' ? 'selected' : '' }}>やや傷や汚れあり</option>
                            <option value="状態が悪い" {{ old('condition') === '状態が悪い' ? 'selected' : '' }}>状態が悪い</option>
                        </select>
                    </div>
                    @error('condition')
                        <p class="sell-form__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 商品名と説明 --}}
            <div class="sell-form__section sell-form__section--bordered">
                <h2 class="sell-form__section-title">商品名と説明</h2>

                <div class="sell-form__field">
                    <label class="sell-form__label" for="name">商品名</label>
                    <input class="sell-form__input" type="text" id="name" name="name" value="{{ old('name') }}">
                    @error('name')
                        <p class="sell-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sell-form__field">
                    <label class="sell-form__label" for="brand_name">ブランド名</label>
                    <input class="sell-form__input" type="text" id="brand_name" name="brand_name" value="{{ old('brand_name') }}">
                    @error('brand_name')
                        <p class="sell-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sell-form__field">
                    <label class="sell-form__label" for="description">商品の説明</label>
                    <textarea class="sell-form__textarea" id="description" name="description">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="sell-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sell-form__field">
                    <label class="sell-form__label" for="price">販売価格</label>
                    <div class="sell-form__price-wrapper">
                        <span class="sell-form__price-prefix">¥</span>
                        <input class="sell-form__price-input" type="number" id="price" name="price"
                            value="{{ old('price') }}" min="0">
                    </div>
                    @error('price')
                        <p class="sell-form__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <button class="sell-form__submit" type="submit">出品する</button>
        </form>
    </div>

    <script>
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(ev) {
                const preview = document.getElementById('imagePreview');
                preview.src = ev.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    </script>
@endsection
