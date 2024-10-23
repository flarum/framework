<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Access;

use Flarum\Extension\ExtensionManager;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeFlagVisibility
{
    public function __construct(
        protected ExtensionManager $extensions
    ) {
    }

    public function __invoke(User $actor, Builder $query): void
    {
        $query
            ->whereHas('post', function (Builder $query) use ($actor) {
                $query->whereVisibleTo($actor);
            })
            ->where(function (Builder $query) use ($actor) {
                if ($this->extensions->isEnabled('flarum-tags')) {
                    $query
                        ->select('flags.*')
                        ->whereHas('post.discussion.tags', function ($query) use ($actor) {
                            $query->whereHasPermission($actor, 'discussion.viewFlags');
                        });

                    if ($actor->hasPermission('discussion.viewFlags')) {
                        $query->orWhereDoesntHave('post.discussion.tags');
                    }
                }

                if (! $actor->hasPermission('discussion.viewFlags')) {
                    $query->orWhere('flags.user_id', $actor->id);
                }
            });
    }
}
