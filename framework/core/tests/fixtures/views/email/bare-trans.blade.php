{{-- A mail view that outputs a translated value directly, WITHOUT routing it
     through the formatter — the way flarum/gdpr's erasure email does. The safe
     markers the mail translator applies must still be put back before the mail
     is sent, or the reader sees `flarumsafevalue…endflarumsafevalue`. --}}
{!! $translator->trans($template, $parameters) !!}
