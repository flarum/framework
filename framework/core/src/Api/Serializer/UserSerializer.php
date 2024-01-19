<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\User\User;

class UserSerializer extends BasicUserSerializer
{
    /**
     * @param User $model
     */
    protected function getDefaultAttributes(object|array $model): array
    {
        $attributes = parent::getDefaultAttributes($model);

        $attributes += [
            'joinTime' => $this->formatDate($model->joined_at),
            'discussionCount' => (int) $model->discussion_count,
            'commentCount' => (int) $model->comment_count,
            'canEdit' => $this->actor->can('edit', $model),
            'canEditCredentials' => $this->actor->can('editCredentials', $model),
            'canEditGroups' => $this->actor->can('editGroups', $model),
            'canDelete' => $this->actor->can('delete', $model),
        ];

        if ($model->getPreference('discloseOnline') || $this->actor->can('viewLastSeenAt', $model)) {
            $attributes += [
                'lastSeenAt' => $this->formatDate($model->last_seen_at)
            ];
        }

        if ($attributes['canEditCredentials'] || $this->actor->id === $model->id) {
            $attributes += [
                'isEmailConfirmed' => (bool) $model->is_email_confirmed,
                'email' => $model->email
            ];
        }

        return $attributes;
    }
}
