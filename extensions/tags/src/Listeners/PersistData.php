<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Listeners;

use Flarum\Tags\Tag;
use Flarum\Tags\Events\DiscussionWasTagged;
use Flarum\Events\DiscussionWillBeSaved;
use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Core\Settings\SettingsRepository;
use Flarum\Tags\TagCountException;

class PersistData
{
    protected $settings;

    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    public function subscribe($events)
    {
        $events->listen(DiscussionWillBeSaved::class, [$this, 'whenDiscussionWillBeSaved']);
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

            $newTags = Tag::whereIn('id', $newTagIds)->get();
            $primaryCount = 0;
            $secondaryCount = 0;

            foreach ($newTags as $tag) {
                if (! $tag->can($actor, 'startDiscussion')) {
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

    protected function validatePrimaryTagCount($count)
    {
        $min = $this->settings->get('tags.min_primary_tags');
        $max = $this->settings->get('tags.max_primary_tags');

        if ($count < $min || $count > $max) {
            throw new TagCountException(['tags' => sprintf('Discussion must have between %d and %d primary tags.', $min, $max)]);
        }
    }

    protected function validateSecondaryTagCount($count)
    {
        $min = $this->settings->get('tags.min_secondary_tags');
        $max = $this->settings->get('tags.max_secondary_tags');

        if ($count < $min || $count > $max) {
            throw new TagCountException(['tags' => sprintf('Discussion must have between %d and %d secondary tags.', $min, $max)]);
        }
    }
}
