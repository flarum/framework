<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Access;

use Flarum\Extension\ExtensionManager;
use Flarum\Tags\Tag;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeFlagVisibility
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    public function __invoke(User $actor, Builder $query)
    {
        if ($this->extensions->isEnabled('flarum-tags')) {
            $query
                ->select('flags.*')
                ->leftJoin('posts', 'posts.id', '=', 'flags.post_id')
                ->leftJoin('discussions', 'discussions.id', '=', 'posts.discussion_id')
                ->whereNotExists(function ($query) use ($actor) {
                    return $query->selectRaw('1')
                        ->from('discussion_tag')
                        ->whereNotIn('tag_id', function ($query) use ($actor) {
                            Tag::query()->setQuery($query->from('tags'))->whereHasPermission($actor, 'discussion.viewFlags')->select('tags.id');
                        })
                        ->whereColumn('discussions.id', 'discussion_id');
                });

            if (! $actor->hasPermission('discussion.viewFlags')) {
                $query->whereExists(function ($query) {
                    return $query->selectRaw('1')
                        ->from('discussion_tag')
                        ->whereColumn('discussions.id', 'discussion_id');
                });
            }
        }

        if (! $actor->hasPermission('discussion.viewFlags')) {
            $query->orWhere('flags.user_id', $actor->id);
        }
    }
}
