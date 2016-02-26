<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Listener;

use Flarum\Core\Exception\PermissionDeniedException;
use Flarum\Core\Exception\ValidationException;
use Flarum\Event\DiscussionWillBeSaved;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Event\DiscussionWasTagged;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Events\Dispatcher;

class SaveTagsToDatabase
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(DiscussionWillBeSaved::class, [$this, 'whenDiscussionWillBeSaved']);
    }

    /**
     * @param DiscussionWillBeSaved $event
     * @throws PermissionDeniedException
     * @throws ValidationException
     */
    public function whenDiscussionWillBeSaved(DiscussionWillBeSaved $event)
    {
        // TODO: clean up, prevent discussion from being created without tags
        if (isset($event->data['relationships']['tags']['data'])) {
            $discussion = $event->discussion;
            $actor = $event->actor;
            $linkage = (array) $event->data['relationships']['tags']['data'];

            $newTagIds = [];
            foreach ($linkage as $link) {
                $newTagIds[] = (int) $link['id'];
            }

            $newTags = Tag::whereIn('id', $newTagIds)->get();
            $primaryCount = 0;
            $secondaryCount = 0;

            foreach ($newTags as $tag) {
                if ($actor->cannot('startDiscussion', $tag)) {
                    throw new PermissionDeniedException;
                }

                if ($tag->position !== null && $tag->parent_id === null) {
                    $primaryCount++;
                } else {
                    $secondaryCount++;
                }
            }

            $this->validatePrimaryTagCount($primaryCount);
            $this->validateSecondaryTagCount($secondaryCount);

            if ($discussion->exists) {
                $oldTags = $discussion->tags()->get();
                $oldTagIds = $oldTags->lists('id');

                if ($oldTagIds == $newTagIds) {
                    return;
                }

                $discussion->raise(
                    new DiscussionWasTagged($discussion, $actor, $oldTags->all())
                );
            }

            $discussion->afterSave(function ($discussion) use ($newTagIds) {
                $discussion->tags()->sync($newTagIds);
            });
        }
    }

    /**
     * @param $count
     * @throws ValidationException
     */
    protected function validatePrimaryTagCount($count)
    {
        $min = $this->settings->get('flarum-tags.min_primary_tags');
        $max = $this->settings->get('flarum-tags.max_primary_tags');

        if ($count < $min || $count > $max) {
            throw new ValidationException([
                'tags' => sprintf('Discussion must have between %d and %d primary tags.', $min, $max)
            ]);
        }
    }

    /**
     * @param $count
     * @throws ValidationException
     */
    protected function validateSecondaryTagCount($count)
    {
        $min = $this->settings->get('flarum-tags.min_secondary_tags');
        $max = $this->settings->get('flarum-tags.max_secondary_tags');

        if ($count < $min || $count > $max) {
            throw new ValidationException([
                'tags' => sprintf('Discussion must have between %d and %d secondary tags.', $min, $max)
            ]);
        }
    }
}
