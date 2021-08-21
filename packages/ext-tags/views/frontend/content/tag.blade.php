@inject('url', 'Flarum\Http\UrlGenerator')

<div class="container">
    <h2>{{ $tag->name }}</h2>
    <p>{{ $tag->description }}</p>

    <ul>
        @foreach ($apiDocument->data as $discussion)
            <li>
                <a href="{{ $url->to('forum')->route('discussion', [
                    'id' => $discussion->attributes->slug
                ]) }}">
                    {{ $discussion->attributes->title }}
                </a>
            </li>
        @endforeach
    </ul>

    @if (isset($apiDocument->links->prev))
        <a href="{{ $url->to('forum')->route('tag', ['slug' => $tag->slug]) }}?page={{ $page - 1 }}">&laquo; {{ $translator->trans('core.views.index.previous_page_button') }}</a>
    @endif

    @if (isset($apiDocument->links->next))
        <a href="{{ $url->to('forum')->route('tag', ['slug' => $tag->slug]) }}?page={{ $page + 1 }}">{{ $translator->trans('core.views.index.next_page_button') }} &raquo;</a>
    @endif
</div>
