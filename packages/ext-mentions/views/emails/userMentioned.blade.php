Hey {{ $user->username }}!

{{ $notification->post->user->username }} mentioned you in a post in {{ $notification->post->discussion->title }}.

{{ \Flarum\Core::config('base_url') }}/d/{{ $notification->post->discussion_id }}/-/{{ $notification->post->number }}

---

{{{ $notification->post->contentPlain }}}
