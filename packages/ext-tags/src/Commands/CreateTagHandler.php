<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Commands;

use Flarum\Tags\Tag;
use Flarum\Core\Forum;
use Flarum\Events\TagWillBeSaved;

class CreateTagHandler
{
    /**
     * @var Forum
     */
    protected $forum;

    /**
     * @param Forum $forum
     */
    public function __construct(Forum $forum)
    {
        $this->forum = $forum;
    }

    /**
     * @param CreateTag $command
     * @return Tag
     */
    public function handle(CreateTag $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $this->forum->assertCan($actor, 'createTag');

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
