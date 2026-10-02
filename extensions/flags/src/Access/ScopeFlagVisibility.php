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
        $query->where(function (Builder $query) use ($actor) {
            $query->where(function (Builder $query) use ($actor) {
                $this->scopePostFlags($actor, $query);
            })->orWhere(function (Builder $query) use ($actor) {
                $query->whereNotNull('target_user_id')
                    ->whereHas('targetUser', fn (Builder $query) => $query->whereVisibleTo($actor));

                // Tag-scoped discussion moderation must never expose account reports.
                if (! $actor->hasPermission('user.viewFlags')) {
                    $query->whereRaw('1 = 0');
                }
            });
        });
    }

    protected function scopePostFlags(User $actor, Builder $query): void
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
                } elseif (! $actor->hasPermission('discussion.viewFlags')) {
                    $query->whereRaw('1 = 0');
                }
            });
    }
}
