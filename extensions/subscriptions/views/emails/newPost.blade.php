{!! $translator->trans('flarum-subscriptions.email.new_post.body', [
'{recipient_display_name}' => $user->display_name,
'{poster_display_name}' => $blueprint->post->user->display_name,
'{title}' => $blueprint->post->discussion->title,
'{url}' => $url->to('forum')->route('discussion', ['id' => $blueprint->post->discussion_id, 'near' => $blueprint->post->number]),
'{content}' => $blueprint->post->content
]) !!}
