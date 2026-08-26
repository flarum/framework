{{-- A mail view that renders the value through the formatter, the way core's
     own notification templates do. The value is restored during rendering; the
     message-level restore must not touch it again. --}}
{!! $formatter->convert($translator->trans($template, $parameters)) !!}
