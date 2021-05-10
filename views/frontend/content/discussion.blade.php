<div class="container">
    <h2>{{ $apiDocument->data->attributes->title }}</h2>

    <div>
        @foreach ($posts as $post)
            <div>
                <?php $user = ! empty($post->relationships->user->data) ? $getResource($post->relationships->user->data) : null; ?>
                <h3>{{ $user ? $user->attributes->username : $translator->trans('core.lib.username.deleted_text') }}</h3>
                <div class="Post-body">
                    {!! $post->attributes->contentHtml !!}
                </div>
            </div>

            <hr>
        @endforeach
    </div>

    @if ($hasPrevPage)
        <a href="{{ $url(['page' => $page - 1]) }}">&laquo; {{ $translator->trans('core.views.discussion.previous_page_button') }}</a>
    @endif

    @if ($hasNextPage)
        <a href="{{ $url(['page' => $page + 1]) }}">{{ $translator->trans('core.views.discussion.next_page_button') }} &raquo;</a>
    @endif
</div>
