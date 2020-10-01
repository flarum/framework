{!! $translator->trans('flarum-mentions.email.user_mentioned.body', [
'{recipient_display_name}' => $user->display_name,
'{mentioner_display_name}' => $blueprint->post->user->display_name,
'{title}' => $blueprint->post->discussion->title,
'{url}' => $url->to('forum')->route('discussion', ['id' => $blueprint->post->discussion_id, 'near' => $blueprint->post->number]),
'{content}' => $blueprint->post->content
]) !!}
