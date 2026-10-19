<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Job;

use Flarum\Discussion\Discussion;
use Flarum\Ideation\Notification\IdeaShippedBlueprint;
use Flarum\Ideation\Status;
use Flarum\Ideation\Vote;
use Flarum\Notification\NotificationSyncer;
use Flarum\Queue\AbstractJob;
use Flarum\User\Guest;
use Flarum\User\User;

/**
 * Tell everyone who voted for an idea that it shipped.
 *
 * Carries ids, not models, so a job that waits in the queue reads the idea as
 * it is when it runs: if it was moved back out of Shipped in the meantime,
 * nobody is told.
 */
class NotifyVoters extends AbstractJob
{
    public function __construct(
        public int $discussionId,
        public int $actorId
    ) {
        parent::__construct();
    }

    public function handle(NotificationSyncer $notifications): void
    {
        /** @var Discussion|null $discussion */
        $discussion = Discussion::query()->find($this->discussionId);

        if (! $discussion || $discussion->ideation_status !== Status::SHIPPED) {
            return;
        }

        $voters = User::query()
            ->whereIn('id', Vote::query()->select('user_id')->where('discussion_id', $discussion->id))
            ->where('id', '!=', $this->actorId)
            ->get();

        /*
         * 🚨 A vote is not a licence to read. Someone who voted while an idea
         * sat in a tag they have since lost access to must not be told its
         * title. One query settles it for an idea any guest can read, which is
         * nearly all of them; only a restricted idea is checked per voter.
         */
        $public = Discussion::query()->whereVisibleTo(new Guest())->whereKey($discussion->id)->exists();

        if (! $public) {
            $voters = $voters->filter(
                fn (User $user) => Discussion::query()->whereVisibleTo($user)->whereKey($discussion->id)->exists()
            );
        }

        $notifications->sync(
            new IdeaShippedBlueprint($discussion, User::query()->find($this->actorId)),
            $voters->values()->all()
        );
    }
}
