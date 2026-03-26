@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')
    <div class="register">
        <div class="register__inner">
            <div class="register__heading">
                <h2 class="register__title">会員登録</h2>
            </div>

            <form class="register-form" action="/register" method="post">
                @csrf

                <div class="register-form__group">
                    <label class="register-form__label" for="name">ユーザー名</label>
                    <div class="register-form__input">
                        <input id="name" type="text" name="name" value="{{ old('name') }}">
                    </div>
                    <p class="register-form__error">
                        @error('name')
                            {{ $message }}
                        @enderror
                    </p>
                </div>

                <div class="register-form__group">
                    <label class="register-form__label" for="email">メールアドレス</label>
                    <div class="register-form__input">
                        <input id="email" type="email" name="email" value="{{ old('email') }}">
                    </div>
                    <p class="register-form__error">
                        @error('email')
                            {{ $message }}
                        @enderror
                    </p>
                </div>

                <div class="register-form__group">
                    <label class="register-form__label" for="password">パスワード</label>
                    <div class="register-form__input">
                        <input id="password" type="password" name="password">
                    </div>
                    <p class="register-form__error">
                        @error('password')
                            {{ $message }}
                        @enderror
                    </p>
                </div>

                <div class="register-form__group">
                    <label class="register-form__label" for="password_confirmation">確認用パスワード</label>
                    <div class="register-form__input">
                        <input id="password_confirmation" type="password" name="password_confirmation">
                    </div>
                    <p class="register-form__error">
                        @error('password_confirmation')
                            {{ $message }}
                        @enderror
                    </p>
                </div>

                <div class="register-form__button">
                    <button class="register-form__button-submit" type="submit">登録する</button>
                </div>
            </form>

            <div class="register__login-link">
                <a href="/login">ログインはこちら</a>
            </div>
        </div>
    </div>
@endsection