@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/address.css') }}">
@endsection

@section('content')
    <div class="address">
        <div class="address__inner">
            <div class="address__heading">
                <h2 class="address__title">住所の変更</h2>
            </div>

            <form class="address-form" action="{{ route('purchase.address', $item) }}" method="post" novalidate>
                @csrf

                <div class="address-form__group">
                    <label class="address-form__label" for="postal_code">郵便番号</label>
                    <div class="address-form__input">
                        <input id="postal_code" type="text" name="postal_code"
                            value="{{ old('postal_code', $address['postal_code']) }}">
                        <p class="address-form__error">
                            @error('postal_code')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                </div>

                <div class="address-form__group">
                    <label class="address-form__label" for="address">住所</label>
                    <div class="address-form__input">
                        <input id="address" type="text" name="address" value="{{ old('address', $address['address']) }}">
                        <p class="address-form__error">
                            @error('address')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                </div>

                <div class="address-form__group">
                    <label class="address-form__label" for="building">建物名</label>
                    <div class="address-form__input">
                        <input id="building" type="text" name="building"
                            value="{{ old('building', $address['building']) }}">
                    </div>
                </div>

                <div class="address-form__button">
                    <button class="address-form__button-submit" type="submit">更新する</button>
                </div>
            </form>
        </div>
    </div>
@endsection