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
use Flarum\Post\CommentPost;
use Flarum\Post\PostRepository;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tobscure\JsonApi\Exception\InvalidParameterException;

class CreateFlagHandler
{
    /**
     * @var PostRepository
     */
    protected $posts;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @param PostRepository $posts
     * @param TranslatorInterface $translator
     * @param SettingsRepositoryInterface $settings
     * @param Dispatcher $events
     */
    public function __construct(PostRepository $posts, TranslatorInterface $translator, SettingsRepositoryInterface $settings, Dispatcher $events)
    {
        $this->posts = $posts;
        $this->translator = $translator;
        $this->settings = $settings;
        $this->events = $events;
    }

    /**
     * @param CreateFlag $command
     * @return Flag
     * @throws InvalidParameterException
     * @throws ValidationException
     */
    public function handle(CreateFlag $command)
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
