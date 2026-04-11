<x-mail::html.information>
    <x-slot:content>
        <p>{{ $translator->trans('core.email.abandoned_extensions.body_intro') }}</p>
        <ul>
            @foreach ($extensionLines as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
        <p>{{ $translator->trans('core.email.abandoned_extensions.body_outro') }}</p>
    </x-slot:content>
</x-mail::html.information>
