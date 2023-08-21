@yield('header')
---------------------

{{ $translator->trans('core.email.greeting', ['displayName' => $username]) }}

@yield('content')

---------------------

@yield('footer')

---------------------
