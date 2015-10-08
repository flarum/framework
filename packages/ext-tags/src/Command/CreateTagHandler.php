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

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Tags\Tag;

class CreateTagHandler
{
    use AssertPermissionTrait;

    /**
     * @param CreateTag $command
     * @return Tag
     */
    public function handle(CreateTag $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $this->assertCan($actor, 'createTag');

        $tag = Tag::build(
            array_get($data, 'attributes.name'),
            array_get($data, 'attributes.slug'),
            array_get($data, 'attributes.description'),
            array_get($data, 'attributes.color'),
            array_get($data, 'attributes.isHidden')
        );

        $tag->save();

        return $tag;
    }
}
