<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Command;

use Flarum\Tags\Event\Deleting;
use Flarum\Tags\TagRepository;

class DeleteTagHandler
{
    /**
     * @var TagRepository
     */
    protected $tags;

    /**
     * @param TagRepository $tags
     */
    public function __construct(TagRepository $tags)
    {
        $this->tags = $tags;
    }

    /**
     * @param DeleteTag $command
     * @return \Flarum\Tags\Tag
     * @throws \Flarum\User\Exception\PermissionDeniedException
     */
    public function handle(DeleteTag $command)
    {
        $actor = $command->actor;

        $tag = $this->tags->findOrFail($command->tagId, $actor);

        $actor->assertCan('delete', $tag);

        event(new Deleting($tag, $actor));

        $tag->delete();

        return $tag;
    }
}
