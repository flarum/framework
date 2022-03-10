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
        static $enoughPrimary;
        static $enoughSecondary;

        if ($ability === 'startDiscussion'
            && $actor->hasPermission($ability)
            && $actor->hasPermission('bypassTagCounts')) {
            return $this->allow();
        }

        if (in_array($ability, ['viewForum', 'startDiscussion'])) {
            if (! isset($enoughPrimary[$actor->id][$ability])) {
                $primaryTagsWhereNeedsPermission = $this->settings->get('flarum-tags.min_primary_tags');
                $primaryTagsWhereHasPermission = Tag::whereHasPermission($actor, $ability)
                    ->where('tags.position', '!=', null)
                    ->count();

                if ($ability === 'viewForum') {
                    $primaryTagsCount = Tag::query()->where('position', '!=', null)->count();
                    $enoughPrimary[$actor->id][$ability] = $primaryTagsWhereHasPermission >= min($primaryTagsCount, $primaryTagsWhereNeedsPermission);
                } else {
                    $enoughPrimary[$actor->id][$ability] = $primaryTagsWhereHasPermission >= $primaryTagsWhereNeedsPermission;
                }
            }

            if (! isset($enoughSecondary[$actor->id][$ability])) {
                $secondaryTagsWhereNeedsPermission = $this->settings->get('flarum-tags.min_secondary_tags');
                $secondaryTagsWhereHasPermission = Tag::whereHasPermission($actor, $ability)
                    ->where('tags.position', '=', null)
                    ->count();

                if ($ability === 'viewForum') {
                    $secondaryTagsCount = Tag::query()->where(['position' => null, 'parent_id' => null])->count();
                    $enoughSecondary[$actor->id][$ability] = $secondaryTagsWhereHasPermission >= min($secondaryTagsCount, $secondaryTagsWhereNeedsPermission);
                } else {
                    $enoughSecondary[$actor->id][$ability] = $secondaryTagsWhereHasPermission >= $secondaryTagsWhereNeedsPermission;
                }
            }

            if ($enoughPrimary[$actor->id][$ability] && $enoughSecondary[$actor->id][$ability]) {
                return $this->allow();
            } else {
                return $this->deny();
            }
        }
    }
}
