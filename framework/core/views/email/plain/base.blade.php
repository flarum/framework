@yield('header')

@if(!isset($greeting) || $greeting !== false)
{{ $translator->trans('core.email.greeting', ['displayName' => $username]) }}
@endif

@yield('content')

@if(!isset($signoff) || $signoff !== false)
- {{ $translator->trans('core.email.signoff', ['forumTitle' => $settings->get('forum_title')]) }} -
@endif


@yield('footer')
