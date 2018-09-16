<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Command;

use Flarum\Tags\TagRepository;
use Flarum\User\AssertPermissionTrait;

class DeleteTagHandler
{
    use AssertPermissionTrait;

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

        $this->assertCan($actor, 'delete', $tag);

        $tag->delete();

        return $tag;
    }
}
