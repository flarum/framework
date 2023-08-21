@extends('flarum.forum::email.plain.base')

@section('header')
    <!-- Specific header for informational emails -->
    {{ $title ?? 'Information' }}
@endsection

@section('content')
    <!-- Content specific to informational emails -->
    {{ $infoContent }}
@endsection

@section('footer')
    <!-- Specific footer for informational emails -->
    This email was sent to {{ $userEmail}} as an informational service related to your account on {{ $forumTitle }}.
@endsection
