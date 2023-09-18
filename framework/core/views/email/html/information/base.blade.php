@extends('flarum.forum::email.html.base')

@section('header')
    <h2>{{ $title ?? $translator->trans('core.email.informational.default_title') }}</h2>
@endsection

@section('content')
    @if(isset($infoContent))
        <p>{!! $formatter->convert($infoContent) !!}</p>
    @else
        @yield('informationContent')
    @endif
    @hasSection('contentPreview')
        <div class="content-preview">
            @yield('contentPreview')
        </div>
    @endif
@endsection

@section('footer')
    <p>{!! $formatter->convert($translator->trans('core.email.informational.footer', ['userEmail' => $userEmail, 'forumUrl' => $url->to('forum')->base(), 'forumTitle' => $settings->get('forum_title')])) !!}</p>
@endsection
