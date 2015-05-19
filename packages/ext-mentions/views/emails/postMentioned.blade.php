Hey {{ $user->username }}!

{{ $notification->sender->username }} replied to your post (#{{ $notification->post->number }}) in {{ $notification->post->discussion->title }}.

{{ \Flarum\Core::config('base_url') }}/d/{{ $notification->reply->discussion_id }}/-/{{ $notification->reply->number }}

---

{{{ $notification->reply->contentPlain }}}
