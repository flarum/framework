@inject('url', 'Flarum\Http\UrlGenerator')

<div class="container">
    <h2>{{ $translator->trans('core.views.index.all_discussions_heading') }}</h2>

    <ul>
        @foreach ($document->data as $discussion)
            <li>
                <a href="{{ $url->to('forum')->route('discussion', [
                    'id' => $discussion->id . (trim($discussion->attributes->slug) ? '-' . $discussion->attributes->slug : '')
                ]) }}">
                    {{ $discussion->attributes->title }}
                </a>
            </li>
        @endforeach
    </ul>

    @if (isset($document->links->prev))
        <a href="{{ $url->to('forum')->route('index') }}?page={{ $page - 1 }}">&laquo; {{ $translator->trans('core.views.index.previous_page_button') }}</a>
    @endif

    @if (isset($document->links->next))
        <a href="{{ $url->to('forum')->route('index') }}?page={{ $page + 1 }}">{{ $translator->trans('core.views.index.next_page_button') }} &raquo;</a>
    @endif
</div>
