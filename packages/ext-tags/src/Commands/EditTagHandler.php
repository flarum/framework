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
use Flarum\Tags\TagRepository;

class EditTagHandler
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
     * @param EditTag $command
     * @return Tag
     * @throws \Flarum\Core\Exceptions\PermissionDeniedException
     */
    public function handle(EditTag $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $tag = $this->tags->findOrFail($command->tagId, $actor);

        $tag->assertCan($actor, 'edit');

        $attributes = array_get($data, 'attributes', []);

        if (isset($attributes['name'])) {
            $tag->name = $attributes['name'];
        }

        if (isset($attributes['slug'])) {
            $tag->slug = $attributes['slug'];
        }

        if (isset($attributes['description'])) {
            $tag->description = $attributes['description'];
        }

        if (isset($attributes['color'])) {
            $tag->color = $attributes['color'];
        }

        if (isset($attributes['isHidden'])) {
            $tag->is_hidden = (bool) $attributes['isHidden'];
        }

        if (isset($attributes['isRestricted'])) {
            $tag->is_restricted = (bool) $attributes['isRestricted'];
        }

        $tag->save();

        return $tag;
    }
}
