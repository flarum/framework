<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Listener;

use Flarum\Discussion\Event\Saving;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Event\DiscussionWasTagged;
use Flarum\Tags\Tag;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Validation\Factory;

class SaveTagsToDatabase
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Factory $validator,
        protected TranslatorInterface $translator
    ) {
    }

    public function handle(Saving $event): void
    {
        $discussion = $event->discussion;
        $actor = $event->actor;

        $newTagIds = [];
        $newTags = [];

        $primaryCount = 0;
        $secondaryCount = 0;

        if (isset($event->data['relationships']['tags']['data'])) {
            $linkage = (array) $event->data['relationships']['tags']['data'];

            foreach ($linkage as $link) {
                $newTagIds[] = (int) $link['id'];
            }

            $newTags = Tag::whereIn('id', $newTagIds)->get();
        }

        if ($discussion->exists && isset($event->data['relationships']['tags']['data'])) {
            $actor->assertCan('tag', $discussion);

            $oldTags = $discussion->tags()->get();
            $oldTagIds = $oldTags->pluck('id')->all();

            if ($oldTagIds == $newTagIds) {
                return;
            }

            foreach ($newTags as $tag) {
                if (! in_array($tag->id, $oldTagIds) && $actor->cannot('addToDiscussion', $tag)) {
                    throw new PermissionDeniedException;
                }
            }

            $discussion->raise(
                new DiscussionWasTagged($discussion, $actor, $oldTags->all())
            );
        }

        if (! $discussion->exists || isset($event->data['relationships']['tags']['data'])) {
            foreach ($newTags as $tag) {
                if (! $discussion->exists && $actor->cannot('startDiscussion', $tag)) {
                    throw new PermissionDeniedException;
                }

                if ($tag->position !== null && $tag->parent_id === null) {
                    $primaryCount++;
                } else {
                    $secondaryCount++;
                }
            }

            if (! $discussion->exists && $primaryCount === 0 && $secondaryCount === 0 && ! $actor->hasPermission('startDiscussion')) {
                throw new PermissionDeniedException;
            }

            if (! $actor->can('bypassTagCounts', $discussion)) {
                $this->validateTagCount('primary', $primaryCount);
                $this->validateTagCount('secondary', $secondaryCount);
            }

            $discussion->afterSave(function ($discussion) use ($newTagIds) {
                $discussion->tags()->sync($newTagIds);
                $discussion->unsetRelation('tags');
            });
        }
    }

    protected function validateTagCount(string $type, int $count): void
    {
        $min = $this->settings->get('flarum-tags.min_'.$type.'_tags');
        $max = $this->settings->get('flarum-tags.max_'.$type.'_tags');
        $key = 'tag_count_'.$type;

        $validator = $this->validator->make(
            [$key => $count],
            [$key => ['numeric', $min === $max ? "size:$min" : "between:$min,$max"]]
        );

        if ($validator->fails()) {
            throw new ValidationException([], ['tags' => $validator->getMessageBag()->first($key)]);
        }
    }
}
