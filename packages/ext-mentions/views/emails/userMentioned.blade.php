Hey {!! $user->display_name !!}!

{!! $blueprint->post->user->display_name !!} mentioned you in a post in {!! $blueprint->post->discussion->title !!}.

{!! app()->url() !!}/d/{!! $blueprint->post->discussion_id !!}/{!! $blueprint->post->number !!}

---

{!! $blueprint->post->content !!}
