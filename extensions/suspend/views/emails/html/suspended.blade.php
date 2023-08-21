@extends('flarum.forum::email.html.information.base')

@section('content')
{!! $translator->trans('flarum-suspend.email.suspended.body', [
'{recipient_display_name}' => $user->display_name,
'{suspension_message}' => $blueprint->user->suspend_message ?? $translator->trans('flarum-suspend.email.no_reason_given'),
]) !!}
@endsection
