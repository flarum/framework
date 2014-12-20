<?php namespace Flarum\Core\Listeners;

use Laracasts\Commander\Events\EventListener;

use Flarum\Core\Users\UserRepository;
use Flarum\Core\Posts\Post;
use Flarum\Core\Posts\Events\ReplyWasPosted;
use Flarum\Core\Posts\Events\PostWasDeleted;
use Flarum\Core\Posts\Events\PostWasHidden;
use Flarum\Core\Posts\Events\PostWasRestored;
use Flarum\Core\Discussions\Events\DiscussionWasStarted;
use Flarum\Core\Discussions\Events\DiscussionWasDeleted;

class UserMetadataUpdater extends EventListener
{
    protected $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    protected function updateRepliesCount($userId, $amount)
    {
        $user = $this->userRepo->find($userId);

        $user->posts_count += $amount;

        $this->userRepo->save($user);
    }

    protected function updateDiscussionsCount($userId, $amount)
    {
        $user = $this->userRepo->find($userId);

        $user->discussions_count += $amount;

        $this->userRepo->save($user);
    }

    public function whenReplyWasPosted(ReplyWasPosted $event)
    {
        $this->updateRepliesCount($event->post->user_id, 1);
    }

    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->updateRepliesCount($event->post->user_id, -1);
    }

    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->updateRepliesCount($event->post->user_id, -1);
    }

    public function whenPostWasRestored(PostWasRestored $event)
    {
        $this->updateRepliesCount($event->post->user_id, 1);
    }

    public function whenDiscussionWasStarted(DiscussionWasStarted $event)
    {
        $this->updateDiscussionsCount($event->discussion->start_user_id, 1);
    }

    public function whenDiscussionWasDeleted(DiscussionWasDeleted $event)
    {
        $this->updateDiscussionsCount($event->discussion->start_user_id, -1);
    }
}
