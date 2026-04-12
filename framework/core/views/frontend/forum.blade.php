{!! $forum['headerHtml'] !!}

<div id="app" class="App">

    <a href="#content" class="sr-only sr-only-focusable-custom">@lang('core.views.layout.skip_to_content')</a>

    <div id="app-navigation" class="App-navigation"></div>

    <div id="drawer" class="App-drawer">

        <header id="header" class="App-header">
            <div id="header-navigation" class="Header-navigation"></div>
            <div class="container">
                <div class="Header-title">
                    <a href="{{ $forum['baseUrl'] }}" id="home-link">
                        @if ($forum['logoUrl'])
                            <img src="{{ $forum['logoUrl'] }}" alt="{{ $forum['title'] }}" class="Header-logo" loading="eager" fetchpriority="high" decoding="async">
                            @if($forum['logoDarkModeUrl'])
                                <img src="{{ $forum['logoDarkModeUrl'] }}" alt="{{ $forum['title'] }}" class="Header-logo Header-logo--dark-mode" loading="eager" fetchpriority="high" decoding="async">
                            @endif
                        @else
                            {{ $forum['title'] }}
                        @endif
                    </a>
                </div>
                <div id="header-primary" class="Header-primary"></div>
                <div id="header-secondary" class="Header-secondary"></div>
            </div>
        </header>

    </div>

    <main class="App-content">
        <div id="notices"></div>

        <div id="content"></div>

        {!! $content !!}

        <div class="App-composer">
            <div class="container">
                <div id="composer"></div>
            </div>
        </div>
    </main>

    <footer class="App-footer" id="footer"></footer>

</div>

{!! $forum['footerHtml'] !!}
