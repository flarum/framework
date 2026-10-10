{!! $formatter->htmlToPlain($header ?? '') !!}

@if(!isset($greeting) || $greeting !== false)
{!! $translator->trans('core.email.greeting', ['displayName' => $username]) !!}
@endif

{!! $formatter->htmlToPlain($content ?? '') !!}

@if(!isset($signoff) || $signoff !== false)
- {!! $translator->trans('core.email.signoff', ['forumTitle' => $settings->get('forum_title')]) !!} -
@endif


{!! $formatter->htmlToPlain($footer ?? '') !!}
