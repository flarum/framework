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

use Flarum\Discussion\Event\Saving;
use Flarum\Foundation\ValidationException;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Event\DiscussionWasTagged;
use Flarum\Tags\Tag;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\Factory;
use Symfony\Component\Translation\TranslatorInterface;

class SaveTagsToDatabase
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Factory
     */
    protected $validator;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param SettingsRepositoryInterface $settings
     * @param Factory $validator
     * @param TranslatorInterface $translator
     */
    public function __construct(SettingsRepositoryInterface $settings, Factory $validator, TranslatorInterface $translator)
    {
        $this->settings = $settings;
        $this->validator = $validator;
        $this->translator = $translator;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Saving::class, [$this, 'whenDiscussionIsSaving']);
    }

    /**
     * @param Saving $event
     * @throws PermissionDeniedException
     * @throws ValidationException
     */
    public function whenDiscussionIsSaving(Saving $event)
    {
        $discussion = $event->discussion;
        $actor = $event->actor;

        // TODO: clean up, prevent discussion from being created without tags
        if (isset($event->data['relationships']['tags']['data'])) {
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

            $this->validateTagCount('primary', $primaryCount);
            $this->validateTagCount('secondary', $secondaryCount);

            if ($discussion->exists) {
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

            $discussion->afterSave(function ($discussion) use ($newTagIds) {
                $discussion->tags()->sync($newTagIds);
            });
        } elseif (! $discussion->exists && ! $actor->hasPermission('startDiscussion')) {
            throw new PermissionDeniedException;
        }
    }

    /**
     * @param string $type
     * @param int $count
     * @throws ValidationException
     */
    protected function validateTagCount($type, $count)
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
