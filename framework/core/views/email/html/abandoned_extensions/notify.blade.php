<x-mail::html.information>
    <x-slot:body>
        <p>{{ $translator->trans('core.email.abandoned_extensions.body_intro') }}</p>
        <ul>
            @foreach ($extensionLines as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
        <p>{{ $translator->trans('core.email.abandoned_extensions.body_outro') }}</p>
    </x-slot:body>
</x-mail::html.information>
