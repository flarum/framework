<x-mail::plain.information>
<x-slot:content>
{{ $translator->trans('core.email.abandoned_extensions.body_intro') }}

@foreach ($extensionLines as $line)
{{ $line }}
@endforeach

{{ $translator->trans('core.email.abandoned_extensions.body_outro') }}
</x-slot:content>
</x-mail::plain.information>
