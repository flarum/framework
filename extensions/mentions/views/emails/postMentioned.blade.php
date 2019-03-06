Hey {!! $user->display_name !!}!

{!! $blueprint->reply->user->display_name !!} replied to your post (#{!! $blueprint->post->number !!}) in {!! $blueprint->post->discussion->title !!}.

{!! app()->url() !!}/d/{!! $blueprint->reply->discussion_id !!}/{!! $blueprint->reply->number !!}

---

{!! $blueprint->reply->content !!}
