<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

class UserSerializer extends BasicUserSerializer
{
    /**
     * @param \Flarum\User\User $user
     * @return array
     */
    protected function getDefaultAttributes($user)
    {
        $attributes = parent::getDefaultAttributes($user);

        $attributes += [
            'joinTime' => $this->formatDate($user->joined_at),
            'discussionCount' => (int) $user->discussion_count,
            'commentCount' => (int) $user->comment_count,
            'canEdit' => $this->actor->can('edit', $user),
            'canEditCredentials' => $this->actor->can('editCredentials', $user),
            'canEditGroups' => $this->actor->can('editGroups', $user),
            'canDelete' => $this->actor->can('delete', $user),
        ];

        if ($user->getPreference('discloseOnline') || $this->actor->can('viewLastSeenAt', $user)) {
            $attributes += [
                'lastSeenAt' => $this->formatDate($user->last_seen_at)
            ];
        }

        if ($attributes['canEditCredentials'] || $this->actor->id === $user->id) {
            $attributes += [
                'isEmailConfirmed' => (bool) $user->is_email_confirmed,
                'email' => $user->email
            ];
        }

        return $attributes;
    }
}
