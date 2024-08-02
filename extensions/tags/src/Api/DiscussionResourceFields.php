<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Event\DiscussionWasTagged;
use Flarum\Tags\Tag;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Validation\Factory;

class DiscussionResourceFields
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Factory $validator,
        protected TranslatorInterface $translator
    ) {
    }

    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('canTag')
                ->get(fn (Discussion $discussion, Context $context) => $context->getActor()->can('tag', $discussion)),
            Schema\Relationship\ToMany::make('tags')
                ->includable()
                ->writable()
                ->required(fn (Context $context, Discussion $discussion) => $context->creating() && ! $context->getActor()->can('bypassTagCounts', $discussion))
                ->set(function (Discussion $discussion, array $newTags, Context $context) {
                    $actor = $context->getActor();

                    $newTagIds = array_map(fn (Tag $tag) => $tag->id, $newTags);

                    $primaryParentCount = 0;
                    $secondaryOrPrimaryChildCount = 0;

                    if ($discussion->exists) {
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

                    foreach ($newTags as $tag) {
                        if (! $discussion->exists && $actor->cannot('startDiscussion', $tag)) {
                            throw new PermissionDeniedException;
                        }

                        if ($tag->position !== null && $tag->parent_id === null) {
                            $primaryParentCount++;
                        } else {
                            $secondaryOrPrimaryChildCount++;
                        }
                    }

                    if (! $discussion->exists && $primaryParentCount === 0 && $secondaryOrPrimaryChildCount === 0 && ! $actor->hasPermission('startDiscussion')) {
                        throw new PermissionDeniedException;
                    }

                    if (! $actor->can('bypassTagCounts', $discussion)) {
                        $this->validateTagCount('primary', $primaryParentCount);
                        $this->validateTagCount('secondary', $secondaryOrPrimaryChildCount);
                    }

                    $discussion->afterSave(function ($discussion) use ($newTagIds) {
                        $discussion->tags()->sync($newTagIds);
                        $discussion->unsetRelation('tags');
                    });
                }),
        ];
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
