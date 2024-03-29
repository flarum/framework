<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Command;

use Flarum\Tags\Event\Creating;
use Flarum\Tags\Tag;
use Flarum\Tags\TagValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class CreateTagHandler
{
    public function __construct(
        protected TagValidator $validator,
        protected Dispatcher $events
    ) {
    }

    public function handle(CreateTag $command): Tag
    {
        $actor = $command->actor;
        $data = $command->data;

        $actor->assertCan('createTag');

        $tag = Tag::build(
            Arr::get($data, 'attributes.name'),
            Arr::get($data, 'attributes.slug'),
            Arr::get($data, 'attributes.description'),
            Arr::get($data, 'attributes.color'),
            Arr::get($data, 'attributes.icon'),
            Arr::get($data, 'attributes.isHidden')
        );

        $parentId = Arr::get($data, 'relationships.parent.data.id');
        $primary = Arr::get($data, 'attributes.primary');

        if ($parentId !== null || $primary) {
            $rootTags = Tag::whereNull('parent_id')->whereNotNull('position');

            if ($parentId === 0 || $primary) {
                $tag->position = $rootTags->max('position') + 1;
            } elseif ($rootTags->find($parentId)) {
                $position = Tag::where('parent_id', $parentId)->max('position');

                $tag->parent()->associate($parentId);
                $tag->position = $position === null ? 0 : $position + 1;
            }
        }

        $this->events->dispatch(new Creating($tag, $actor, $data));

        $this->validator->assertValid($tag->getAttributes());

        $tag->save();

        return $tag;
    }
}
