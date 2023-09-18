@extends('flarum.forum::email.html.base')

@section('header')
    <h2>{{ $title ?? $translator->trans('core.email.informational.default_title') }}</h2>
@endsection

@section('content')
    <p>{!! $formatter->convert($infoContent) !!}<p>
    <div class="content-preview">
        @yield('contentPreview')
    </div>
@endsection

@section('footer')
    <p>{!! $translator->trans('core.email.informational.footer', ['userEmail' => $userEmail, 'forumTitle' => '<a href="' . $url->to('forum')->base() . '">' . $settings->get('forum_title') . '</a>']) !!}</p>
@endsection
