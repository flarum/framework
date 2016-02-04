<?php
$url = app('Flarum\Forum\UrlGenerator');
?>
<div class="container">
    <h2>{{ $translator->trans('core.ref.all_discussions') }}</h2>

    <ul>
        @foreach ($document->data as $discussion)
            <li>
                <a href="{{ $url->toRoute('discussion', [
                    'id' => $discussion->id . '-' . $discussion->attributes->slug
                ]) }}">
                    {{ $discussion->attributes->title }}
                </a>
            </li>
        @endforeach
    </ul>

    <a href="{{ $url->toRoute('index') }}?page={{ $page + 1 }}">{{ $translator->trans('core.basic.next_page_button') }} &raquo;</a>
</div>
