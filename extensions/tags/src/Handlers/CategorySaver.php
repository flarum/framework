<?php namespace Flarum\Categories\Handlers;

use Flarum\Categories\Events\DiscussionWasMoved;
use Flarum\Core\Events\DiscussionWillBeSaved;

class CategorySaver
{
    public function subscribe($events)
    {
        $events->listen('Flarum\Core\Events\DiscussionWillBeSaved', __CLASS__.'@whenDiscussionWillBeSaved');
    }

    public function whenDiscussionWillBeSaved(DiscussionWillBeSaved $event)
    {
        if (isset($event->command->data['links']['category']['linkage'])) {
            $linkage = $event->command->data['links']['category']['linkage'];

            $categoryId = (int) $linkage['id'];
            $discussion = $event->discussion;
            $user = $event->command->user;

            $oldCategoryId = (int) $discussion->category_id;

            if ($oldCategoryId === $categoryId) {
                return;
            }

            $discussion->category_id = $categoryId;
            $discussion->raise(new DiscussionWasMoved($discussion, $user, $oldCategoryId));
        }
    }
}
