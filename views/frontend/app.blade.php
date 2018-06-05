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

@if ($allowJs)
    <script>
        document.getElementById('flarum-loading').style.display = 'block';
    </script>

    {!! $js !!}

    <script>
        document.getElementById('flarum-loading').style.display = 'none';
        @if (! array_get($forum, 'data.attributes.debug'))
        try {
        @endif
            flarum.app.boot(@json($payload));
        @if (! array_get($forum, 'data.attributes.debug'))
        } catch (e) {
            window.location += (window.location.search ? '&' : '?') + 'nojs=1';
            throw e;
        }
        @endif
    </script>
@else
    <script>
        window.history.replaceState(null, null, window.location.toString().replace(/([&?]nojs=1$|nojs=1&)/, ''));
    </script>
@endif

{!! $foot !!}
</body>
</html>
