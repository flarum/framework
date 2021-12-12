{!! $translator->trans('flarum-suspend.email.unsuspended.body', [
'{recipient_display_name}' => $user->display_name,
'{forum_url}' => $url->to('forum')->base(),
]) !!}
