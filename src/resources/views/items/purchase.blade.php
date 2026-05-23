@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/purchase.css') }}">
@endsection

@section('content')
    <form class="purchase" action="{{ route('purchase.store', $item) }}" method="post">
    @csrf
        <div class="purchase__left">
            <div class="purchase__item">
                <div class="purchase__item-image">
                    <img src="{{ $item->image_path }}" alt="{{ $item->name }}">
                </div>
                <div class="purchase__item-info">
                    <p class="purchase__item-name">{{ $item->name }}</p>
                    <p class="purchase__item-price">¥{{ number_format($item->price) }}</p>
                </div>
            </div>

            <hr class="purchase__divider">

            <section class="purchase__section">
                <h2 class="purchase__section-title">支払い方法</h2>
                <select class="purchase__select" id="payment_method" name="payment_method">
                    <option value="" disabled selected>選択してください</option>
                    <option value="コンビニ支払い">コンビニ支払い</option>
                    <option value="カード支払い">カード支払い</option>
                </select>
                @error('payment_method')
                    <p class="purchase__error">{{ $message }}</p>
                @enderror
            </section>

            <hr class="purchase__divider">

            <section class="purchase__section">
                <div class="purchase__address-header">
                    <h2 class="purchase__section-title">配送先</h2>
                    <a class="purchase__address-change" href="{{ route('purchase.address', $item) }}">変更する</a>
                </div>
                @if ($address['postal_code'])
                    <p class="purchase__address-line">〒 {{ $address['postal_code'] }}</p>
                    <p class="purchase__address-line">{{ $address['address'] }}{{ $address['building'] }}</p>
                @else
                    <p class="purchase__address-line purchase__address-line--empty">住所が登録されていません</p>
                @endif
                @error('postal_code')
                    <p class="purchase__error">{{ $message }}</p>
                @enderror
            </section>

            <hr class="purchase__divider">
        </div>

        <div class="purchase__right">
            <div class="purchase__summary">
                <table class="purchase__summary-table">
                    <tr class="purchase__summary-row">
                        <th class="purchase__summary-label">商品代金</th>
                        <td class="purchase__summary-value">¥{{ number_format($item->price) }}</td>
                    </tr>
                    <tr class="purchase__summary-row">
                        <th class="purchase__summary-label">支払い方法</th>
                        <td class="purchase__summary-value" id="summary_payment">
                            <span class="purchase__summary-payment-placeholder">-</span>
                        </td>
                    </tr>
                </table>
            </div>

            <button class="purchase__buy-button" type="submit">購入する</button>
        </div>
    </form>

    <script>
        const select = document.getElementById('payment_method');
        const summaryPayment = document.getElementById('summary_payment');

        select.addEventListener('change', function () {
            summaryPayment.textContent = this.value || '-';
        });
    </script>
@endsection
