<?php namespace Flarum\Tags\Listeners;

use Flarum\Tags\Tag;
use Flarum\Tags\Events\DiscussionWasTagged;
use Flarum\Events\DiscussionWillBeSaved;
use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Exceptions\PermissionDeniedException;

class PersistData
{
    public function subscribe($events)
    {
        $events->listen(DiscussionWillBeSaved::class, __CLASS__.'@whenDiscussionWillBeSaved');
    }

    public function whenDiscussionWillBeSaved(DiscussionWillBeSaved $event)
    {
        if (isset($event->data['relationships']['tags']['data'])) {
            $discussion = $event->discussion;
            $actor = $event->actor;
            $linkage = (array) $event->data['relationships']['tags']['data'];

            $newTagIds = [];
            foreach ($linkage as $link) {
                $newTagIds[] = (int) $link['id'];
            }

            $newTags = Tag::whereIn('id', $newTagIds);
            foreach ($newTags as $tag) {
                if (! $tag->can($actor, 'startDiscussion')) {
                    throw new PermissionDeniedException;
                }
            }

            $oldTags = [];

            if ($discussion->exists) {
                $oldTags = $discussion->tags()->get();
                $oldTagIds = $oldTags->lists('id');

                if ($oldTagIds == $newTagIds) {
                    return;
                }

                $discussion->raise(new DiscussionWasTagged($discussion, $actor, $oldTags->all()));
            }

            Discussion::saved(function ($discussion) use ($newTagIds) {
                $discussion->tags()->sync($newTagIds);
            });
        }
    }
}
