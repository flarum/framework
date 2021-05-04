<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Access;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Tag;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class GlobalPolicy extends AbstractPolicy
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param Flarum\User\User $actor
     * @param string $ability
     * @return bool|void
     */
    public function can(User $actor, string $ability)
    {
        if (in_array($ability, ['viewDiscussions', 'startDiscussion'])) {
            if ($actor->hasPermission($ability) && $actor->hasPermission('bypassTagCounts')) {
                return $this->allow();
            }
            $enoughPrimary = Tag::queryIdsWhereCan(Tag::query()->getQuery(), $actor, $ability, true, false)->count() >= $this->settings->get('flarum-tags.min_primary_tags');
            $enoughSecondary = Tag::queryIdsWhereCan(Tag::query()->getQuery(), $actor, $ability, false, true)->count() >= $this->settings->get('flarum-tags.min_secondary_tags');

            if ($enoughPrimary && $enoughSecondary) {
                return $this->allow();
            } else {
                return $this->deny();
            }
        }
    }
}
