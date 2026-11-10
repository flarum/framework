<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Scope;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class View
{
    public function __invoke(User $actor, Builder $query): void
    {
        if ($actor->hasPermission('flarum-audit.view')) {
            return;
        }

        if ($actor->hasPermission('flarum-audit.viewLimited')) {
            /** @var SettingsRepositoryInterface $settings */
            $settings = resolve(SettingsRepositoryInterface::class);

            $limitedActions = $settings->get('flarum-audit.limitedActions');

            // If the setting has no value, it means everything is allowed
            if (! $limitedActions) {
                return;
            }

            $patterns = [];
            $exacts = [];

            foreach (explode(',', $limitedActions) as $action) {
                if (Str::endsWith($action, '.*')) {
                    $patterns[] = str_replace('*', '%', $action);
                } else {
                    $exacts[] = $action;
                }
            }

            $query->where(function (Builder $query) use ($patterns, $exacts) {
                if (count($exacts)) {
                    $query->whereIn('action', $exacts);
                }

                foreach ($patterns as $pattern) {
                    $query->orWhere('action', 'like', $pattern);
                }
            });

            return;
        }

        // Default: no access
        $query->whereRaw('1=0');
    }
}
