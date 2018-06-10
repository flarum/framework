<!doctype html>
<html dir="{{ $direction }}" lang="{{ $language }}">
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
        </script>

        {!! $js !!}

        <script>
            document.getElementById('flarum-loading').style.display = 'none';

            try {
                flarum.app.boot(@json($payload));
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
