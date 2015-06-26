Hey {{ $user->username }}!

{{ $notification->post->user->username }} made a post in a discussion you're following: {{ $notification->post->discussion->title }}

To view the new activity, check out the following link:
{{ \Flarum\Core::config('base_url') }}/d/{{ $notification->post->discussion_id }}/-/{{ $notification->post->number }}

---

{{ strip_tags($notification->post->contentHtml) }}

---

You won't receive any more notifications about this discussion until you're up-to-date.
