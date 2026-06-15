<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COACHTECH Flea Market</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <a class="header__logo" href="/">
                <img src="{{ asset('images/logo/COACHTECHヘッダーロゴ.png') }}" alt="COACHTECH">
            </a>

            @guest
                @if (request()->routeIs('items.index', 'items.show'))
                    <form class="header-search" action="/" method="get">
                        <input class="header-search__input" type="text" name="keyword" placeholder="なにをお探しですか？"
                            value="{{ request('keyword') }}">
                        @if(request('tab') === 'mylist')
                            <input type="hidden" name="tab" value="mylist">
                        @endif
                    </form>

                    <nav class="header-nav">
                        <ul class="header-nav__list">
                            <li class="header-nav__item">
                                <a class="header-nav__link" href="/login">ログイン</a>
                            </li>
                            <li class="header-nav__item">
                                <a class="header-nav__link" href="/login">マイページ</a>
                            </li>
                            <li class="header-nav__item">
                                <a class="header-nav__sell-link" href="{{ route('items.create') }}">出品</a>
                            </li>
                        </ul>
                    </nav>
                @endif
            @endguest

            @auth
                @if (!request()->routeIs('verification.notice'))
                    @if (request()->routeIs('items.index', 'items.show', 'mypage.profile'))
                        <form class="header-search" action="/" method="get">
                            <input class="header-search__input" type="text" name="keyword" placeholder="なにをお探しですか？"
                                value="{{ request('keyword') }}">
                            @if(request('tab') === 'mylist')
                                <input type="hidden" name="tab" value="mylist">
                            @endif
                        </form>
                    @endif

                    <nav class="header-nav">
                        <ul class="header-nav__list">
                            <li class="header-nav__item">
                                <form action="/logout" method="post">
                                    @csrf
                                    <button class="header-nav__button" type="submit">ログアウト</button>
                                </form>
                            </li>
                            <li class="header-nav__item">
                                <a class="header-nav__link" href="/mypage">マイページ</a>
                            </li>
                            <li class="header-nav__item">
                                <a class="header-nav__sell-link" href="{{ route('items.create') }}">出品</a>
                            </li>
                        </ul>
                    </nav>
                @endif
            @endauth
        </div>
    </header>

    <main class="main">
        @yield('content')
    </main>
</body>

</html>
