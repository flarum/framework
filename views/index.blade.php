<?php
$url = app('Flarum\Http\UrlGeneratorInterface');
?>
<div class="container">
    <h2>All Discussions</h2>

    <ul>
        @foreach ($document->data as $discussion)
            <li>
                <a href="{{ $url->toRoute('flarum.forum.discussion', [
                    'id' => $discussion->id . '-' . $discussion->attributes->title
                ]) }}">
                    {{ $discussion->attributes->title }}
                </a>
            </li>
        @endforeach
    </ul>

    <a href="{{ $url->toRoute('flarum.forum.index') }}?page={{ $page + 1 }}">Next Page &raquo;</a>
</div>
