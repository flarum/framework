@yield('header')


{{ $translator->trans('core.email.greeting', ['displayName' => $username]) }}

@yield('content')

- {{ $translator->trans('core.email.signoff', ['forumTitle' => $settings->get('forum_title')]) }} -



@yield('footer')
