<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Command;

use Flarum\Tags\Event\Deleting;
use Flarum\Tags\Tag;
use Flarum\Tags\TagRepository;
use Illuminate\Contracts\Events\Dispatcher;

class DeleteTagHandler
{
    public function __construct(
        protected TagRepository $tags,
        protected Dispatcher $events
    ) {
    }

    public function handle(DeleteTag $command): Tag
    {
        $actor = $command->actor;

        $tag = $this->tags->findOrFail($command->tagId, $actor);

        $actor->assertCan('delete', $tag);

        $this->events->dispatch(new Deleting($tag, $actor));

        $tag->delete();

        return $tag;
    }
}
