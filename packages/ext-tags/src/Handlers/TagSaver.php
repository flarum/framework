<?php namespace Flarum\Tags\Handlers;

use Flarum\Tags\Tag;
use Flarum\Tags\Events\DiscussionWasTagged;
use Flarum\Core\Events\DiscussionWillBeSaved;
use Flarum\Core\Models\Discussion;
use Flarum\Core\Exceptions\PermissionDeniedException;

class TagSaver
{
    public function subscribe($events)
    {
        $events->listen('Flarum\Core\Events\DiscussionWillBeSaved', __CLASS__.'@whenDiscussionWillBeSaved');
    }

    public function whenDiscussionWillBeSaved(DiscussionWillBeSaved $event)
    {
        if (isset($event->command->data['links']['tags']['linkage'])) {
            $discussion = $event->discussion;
            $user = $event->command->user;
            $linkage = (array) $event->command->data['links']['tags']['linkage'];

            $newTagIds = [];
            foreach ($linkage as $link) {
                $newTagIds[] = (int) $link['id'];
            }

            $newTags = Tag::whereIn('id', $newTagIds);
            foreach ($newTags as $tag) {
                if (! $tag->can($user, 'startDiscussion')) {
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
            }

            // @todo is there a better (safer) way to do this?
            // maybe store some info on the discussion model and then use the
            // DiscussionWasTagged event to actually save the data?
            Discussion::saved(function ($discussion) use ($newTagIds) {
                $discussion->tags()->sync($newTagIds);
            });

            if ($discussion->exists) {
                $discussion->raise(new DiscussionWasTagged($discussion, $user, $oldTags->all()));
            }
        }
    }
}
