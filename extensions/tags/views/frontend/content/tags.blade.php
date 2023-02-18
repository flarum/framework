@inject('url', 'Flarum\Http\UrlGenerator')

<div class="container">
    <h1>{{ $translator->trans('flarum-tags.forum.index.tags_link') }}</h1>

    @foreach ([$primaryTags, $secondaryTags] as $category)
        <ul>
            @foreach ($category->pluck('attributes', 'id') as $id => $tag)
                <li>
                    <a href="{{ $url->to('forum')->route('tag', [
                                'slug' => $tag['slug']
                            ]) }}">
                        {{ $tag['name'] }}
                    </a>

                    @if ($children->has($id))
                        <ul>
                            @foreach ($children->get($id) as $child)
                                <li>
                                    <a href="{{ $url->to('forum')->route('tag', [
                                                'slug' => $child['attributes']['slug']
                                            ]) }}">
                                        {{ $child['attributes']['name'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
            </ul>
    @endforeach
</div>
