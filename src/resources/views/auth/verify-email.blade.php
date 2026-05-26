@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@section('content')
    <div class="verify-email">
        <div class="verify-email__inner">
            <p class="verify-email__message">
                登録していただいたメールアドレスに認証メールを送付しました。<br>
                メール認証を完了してください。
            </p>

            <div class="verify-email__button">
                <a class="verify-email__button-link" href="http://localhost:8025" target="_blank">認証はこちらから</a>
            </div>

            <div class="verify-email__resend">
                @if (session('status') === 'verification-link-sent')
                    <p class="verify-email__resend-status">認証メールを再送しました。</p>
                @endif
                <form action="{{ route('verification.send') }}" method="post">
                    @csrf
                    <button type="submit" class="verify-email__resend-link">認証メールを再送する</button>
                </form>
            </div>
        </div>
    </div>
@endsection
