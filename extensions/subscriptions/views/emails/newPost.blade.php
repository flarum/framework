Hey {!! $user->display_name !!}!

{!! $blueprint->post->user->display_name !!} made a post in a discussion you're following: {!! $blueprint->post->discussion->title !!}

To view the new activity, check out the following link:
{!! app()->url() !!}/d/{!! $blueprint->post->discussion_id !!}/{!! $blueprint->post->number !!}

---

{!! $blueprint->post->content !!}

---

You won't receive any more notifications about this discussion until you're up-to-date.
