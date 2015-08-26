Hey {{ $user->username }}!

{{ $blueprint->post->user->username }} mentioned you in a post in {{ $blueprint->post->discussion->title }}.

{{ \Flarum\Core::url() }}/d/{{ $blueprint->post->discussion_id }}/{{ $blueprint->post->number }}

---

{{ strip_tags($blueprint->post->contentHtml) }}
