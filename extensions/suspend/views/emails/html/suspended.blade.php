@extends('flarum.forum::email.html.information.base')

@section('informationContent')
{!! $formatter->convert($translator->trans('flarum-suspend.email.suspended.html.body', [
'{forumTitle}' => $settings->get('forum_title')
])) !!}
@endsection

@section('contentPreview')
    {!! $formatter->convert($blueprint->user->suspend_message ?? $translator->trans('flarum-suspend.email.no_reason_given')) !!}
@endsection
