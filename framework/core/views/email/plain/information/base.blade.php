@extends('flarum.forum::email.plain.base')

@section('header')
{{ $title ?? $translator->trans('core.email.informational.default_title') }}
@endsection

@section('content')
{{ $infoContent }}
@endsection

@section('footer')
{!! $translator->trans('core.email.informational.footer', ['userEmail' => $userEmail, 'forumTitle' => $forumTitle]) !!}
@endsection
