<!doctype html>
<html @if ($direction) dir="{{ $direction }}" @endif @if ($language) lang="{{ $language }}" @endif @class($extraClasses) {!! $extraAttributes !!}>
    <head>
        <meta charset="utf-8">
        <title>{{ $title }}</title>

        {!! $head !!}
    </head>

    <body>
        {!! $layout !!}

        <div id="modal"></div>
        <div id="alerts"></div>

        <script>
            document.getElementById('flarum-loading').style.display = 'block';
            var flarum = {extensions: {}, debug: @js($debug)};
        </script>

        {!! $js !!}

        <script id="flarum-rev-manifest" type="application/json">@json($revisions)</script>

        <script id="flarum-json-payload" type="application/json">@json($payload)</script>

        <script>
            const data = JSON.parse(document.getElementById('flarum-json-payload').textContent);
            document.getElementById('flarum-loading').style.display = 'none';

            try {
                app.load(data);
                app.bootExtensions(flarum.extensions);
                app.boot();
            } catch (e) {
                var error = document.getElementById('flarum-loading-error');
                error.innerHTML += document.getElementById('flarum-content').textContent;
                error.style.display = 'block';
                throw e;
            }
        </script>

        {!! $foot !!}
    </body>
</html>
