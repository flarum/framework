<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Command;

use Carbon\Carbon;
use Flarum\Flags\Event\Created;
use Flarum\Flags\Flag;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use Flarum\Post\CommentPost;
use Flarum\Post\PostRepository;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;
use Tobscure\JsonApi\Exception\InvalidParameterException;

class CreateFlagHandler
{
    public function __construct(
        protected PostRepository $posts,
        protected TranslatorInterface $translator,
        protected SettingsRepositoryInterface $settings,
        protected Dispatcher $events
    ) {
    }

    public function handle(CreateFlag $command): Flag
    {
        $actor = $command->actor;
        $data = $command->data;

        $postId = Arr::get($data, 'relationships.post.data.id');
        $post = $this->posts->findOrFail($postId, $actor);

        if (! ($post instanceof CommentPost)) {
            throw new InvalidParameterException;
        }

        $actor->assertCan('flag', $post);

        if ($actor->id === $post->user_id && ! $this->settings->get('flarum-flags.can_flag_own')) {
            throw new PermissionDeniedException();
        }

        if (Arr::get($data, 'attributes.reason') === null && Arr::get($data, 'attributes.reasonDetail') === '') {
            throw new ValidationException([
                'message' => $this->translator->trans('flarum-flags.forum.flag_post.reason_missing_message')
            ]);
        }

        Flag::unguard();

        $flag = Flag::firstOrNew([
            'post_id' => $post->id,
            'user_id' => $actor->id
        ]);

        $flag->post_id = $post->id;
        $flag->user_id = $actor->id;
        $flag->type = 'user';
        $flag->reason = Arr::get($data, 'attributes.reason');
        $flag->reason_detail = Arr::get($data, 'attributes.reasonDetail');
        $flag->created_at = Carbon::now();

        $flag->save();

        $this->events->dispatch(new Created($flag, $actor, $data));

        return $flag;
    }
}
