<header id="main-header">
    <div class="flex-container">
        <div class="flex-container__block">
            <a href="http://promodo.dev">
                <img src="/images/logo.png" class="flex-container__main-logo">
            </a>
            
        </div>
        <div class="flex-container__block">
            @auth
            <div class="login-panel">
                <div class="login-panel__user-icon">
                    <img class="login-panel__user-icon" src="{{ Auth::user()->avatar }}" alt="">
                </div>
                <div class="login-panel__user-info">
                    <p class="login-panel__info-name">{{ Auth::user()->name }}</p>
                    <p class="login-panel__user-logout"><a href="{{ route('logout')}}">Выйти</a></p>
                </div>
            </div>
            @endauth
        </div>
    </div>
</header>