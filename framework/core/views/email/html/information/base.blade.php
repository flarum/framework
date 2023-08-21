@extends('flarum.forum::email.html.base')

@section('header')
    <!-- Specific header for informational emails -->
    <h2>{{ $title ?? $translator->trans('core.email.informational.default_title') }}</h2>
@endsection

@section('content')
    <!-- Content specific to informational emails -->
    <p>{{ $infoContent }}<p>
@endsection

@section('footer')
    <!-- Specific footer for informational emails -->
    <p>{!! $translator->trans('core.email.informational.footer', ['userEmail' => $userEmail, 'forumTitle' => '<a href="' . $url->to('forum')->base() . '">' . $settings->get('forum_title') . '</a>']) !!}</p>
@endsection
