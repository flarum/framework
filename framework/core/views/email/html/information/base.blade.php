@extends('flarum.forum::email.html.base')

@section('header')
    <!-- Specific header for informational emails -->
    <h2>{{ $title ?? 'Information' }}</h2>
@endsection

@section('content')
    <!-- Content specific to informational emails -->
    <p>{{ $infoContent }}<p>
@endsection

@section('footer')
    <!-- Specific footer for informational emails -->
    <p>This email was sent to {{ $userEmail}} as an informational service related to your account on {{ $forumTitle }}.</p>
@endsection
