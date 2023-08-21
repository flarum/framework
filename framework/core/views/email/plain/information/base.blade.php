@extends('flarum.forum::email.plain.base')

@section('header')
{{ $title ?? $translator->trans('core.email.informational.default_title') }}
@endsection

@section('content')
{{ $infoContent }}
@endsection

@section('footer')
This email was sent to {{ $userEmail}} as an informational service related to your account on {{ $forumTitle }}.
@endsection
