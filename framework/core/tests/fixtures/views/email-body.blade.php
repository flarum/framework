{{-- Mirrors what notification email views do: translate a markup template with
     user-supplied values, then render the result through the formatter. --}}
{!! $formatter->convert($translator->trans($template, $parameters)) !!}
