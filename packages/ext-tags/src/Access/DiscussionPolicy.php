<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Access;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Tag;
use Flarum\User\AbstractPolicy;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class DiscussionPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = Discussion::class;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param User $actor
     * @param string $ability
     * @param Discussion $discussion
     * @return bool
     */
    public function can(User $actor, $ability, Discussion $discussion)
    {
        // Wrap all discussion permission checks with some logic pertaining to
        // the discussion's tags. If the discussion has a tag that has been
        // restricted, the user must have the permission for that tag.
        $tags = $discussion->tags;

        if (count($tags)) {
            $restricted = false;

            foreach ($tags as $tag) {
                if ($tag->is_restricted) {
                    if (! $actor->hasPermission('tag'.$tag->id.'.discussion.'.$ability)) {
                        return false;
                    }

                    $restricted = true;
                }
            }

            if ($restricted) {
                return true;
            }
        }
    }

    /**
     * @param User $actor
     * @param Builder $query
     */
    public function find(User $actor, Builder $query)
    {
        // Hide discussions which have tags that the user is not allowed to see.
        $query->whereNotIn('discussions.id', function ($query) use ($actor) {
            return $query->select('discussion_id')
                ->from('discussion_tag')
                ->whereIn('tag_id', Tag::getIdsWhereCannot($actor, 'viewDiscussions'));
        });

        // Hide discussions with no tags if the user doesn't have that global
        // permission.
        if (! $actor->hasPermission('viewDiscussions')) {
            $query->has('tags');
        }
    }

    /**
     * @param User $actor
     * @param Builder $query
     * @param string $ability
     */
    public function findWithPermission(User $actor, Builder $query, $ability)
    {
        // If a discussion requires a certain permission in order for it to be
        // visible, then we can check if the user has been granted that
        // permission for any of the discussion's tags.
        $query->whereIn('discussions.id', function ($query) use ($actor, $ability) {
            return $query->select('discussion_id')
                ->from('discussion_tag')
                ->whereIn('tag_id', Tag::getIdsWhereCan($actor, 'discussion.'.$ability));
        });
    }

    /**
     * This method checks, if the user is still allowed to edit the tags
     * based on the configuration item.
     *
     * @param User $actor
     * @param Discussion $discussion
     * @return bool
     */
    public function tag(User $actor, Discussion $discussion)
    {
        if ($discussion->user_id == $actor->id && $actor->can('reply', $discussion)) {
            $allowEditTags = $this->settings->get('allow_tag_change');

            if ($allowEditTags === '-1'
                || ($allowEditTags === 'reply' && $discussion->participant_count <= 1)
                || (is_numeric($allowEditTags) && $discussion->created_at->diffInMinutes(new Carbon) < $allowEditTags)
            ) {
                return true;
            }
        }
    }
}
