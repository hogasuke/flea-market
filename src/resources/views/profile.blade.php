@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endsection

@section('content')
    <div class="profile">
        <div class="profile__inner">
            <div class="profile__heading">
                <h2 class="profile__title">プロフィール設定</h2>
            </div>

            <form class="profile-form" action="/mypage/profile" method="post" enctype="multipart/form-data"
                novalidate>
                @csrf

                <div class="profile-form__avatar">
                    <div class="profile-form__avatar-image">
                        <img id="avatar-preview"
                            src="{{ $user->profile_image ? asset('storage/' . $user->profile_image) : '' }}" alt="プロフィール画像"
                            class="{{ $user->profile_image ? '' : 'profile-form__avatar-placeholder' }}">
                    </div>
                    <div class="profile-form__avatar-controls">
                        <label class="profile-form__avatar-button" for="profile_image">画像を選択する</label>
                        <input id="profile_image" type="file" name="profile_image" accept="image/*"
                            class="profile-form__avatar-input">
                        <p class="profile-form__error">
                            @error('profile_image')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                </div>

                <div class="profile-form__group">
                    <label class="profile-form__label" for="name">ユーザー名</label>
                    <div class="profile-form__input">
                        <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}">
                    </div>
                    <p class="profile-form__error">
                        @error('name')
                            {{ $message }}
                        @enderror
                    </p>
                </div>

                <div class="profile-form__group">
                    <label class="profile-form__label" for="postal_code">郵便番号</label>
                    <div class="profile-form__input">
                        <input id="postal_code" type="text" name="postal_code"
                            value="{{ old('postal_code', $user->postal_code) }}">
                    </div>
                    <p class="profile-form__error">
                        @error('postal_code')
                            {{ $message }}
                        @enderror
                    </p>
                </div>

                <div class="profile-form__group">
                    <label class="profile-form__label" for="address">住所</label>
                    <div class="profile-form__input">
                        <input id="address" type="text" name="address" value="{{ old('address', $user->address) }}">
                    </div>
                    <p class="profile-form__error">
                        @error('address')
                            {{ $message }}
                        @enderror
                    </p>
                </div>

                <div class="profile-form__group">
                    <label class="profile-form__label" for="building">建物名</label>
                    <div class="profile-form__input">
                        <input id="building" type="text" name="building" value="{{ old('building', $user->building) }}">
                    </div>
                    <p class="profile-form__error">
                        @error('building')
                            {{ $message }}
                        @enderror
                    </p>
                </div>

                <div class="profile-form__button">
                    <button class="profile-form__button-submit" type="submit">更新する</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('profile_image').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function (ev) {
                const img = document.getElementById('avatar-preview');
                img.src = ev.target.result;
                img.classList.remove('profile-form__avatar-placeholder');
            };
            reader.readAsDataURL(file);
        });
    </script>
@endsection