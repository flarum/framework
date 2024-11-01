{{ strip_tags($header ?? '') }}

@if(!isset($greeting) || $greeting !== false)
{{ $translator->trans('core.email.greeting', ['displayName' => $username]) }}
@endif

{{ strip_tags($content ?? '') }}

@if(!isset($signoff) || $signoff !== false)
- {{ $translator->trans('core.email.signoff', ['forumTitle' => $settings->get('forum_title')]) }} -
@endif


{{ strip_tags($footer ?? '') }}
