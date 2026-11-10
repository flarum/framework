{{-- Mirrors what every notification email view does: translate a markup
     template with user values, then render it through the formatter. --}}
{!! $formatter->convert($translator->trans($template, $parameters)) !!}
