<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\User\Gate;

class UserSerializer extends BasicUserSerializer
{
    /**
     * @var \Flarum\User\Gate
     */
    protected $gate;

    /**
     * @param Gate $gate
     */
    public function __construct(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * @param \Flarum\User\User $user
     * @return array
     */
    protected function getDefaultAttributes($user)
    {
        $attributes = parent::getDefaultAttributes($user);

        $gate = $this->gate->forUser($this->actor);

        $attributes += [
            'joinTime'           => $this->formatDate($user->joined_at),
            'discussionCount'    => (int) $user->discussion_count,
            'commentCount'       => (int) $user->comment_count,
            'canEditUsername'    => $gate->allows('user.edit.username', $user),
            'canEditCredentials' => $gate->allows('user.edit.credentials', $user),
            'canEditGroups'      => $gate->allows('user.edit.groups', $user),
            'canDelete'          => $gate->allows('delete', $user),
        ];

        if ($user->getPreference('discloseOnline') || $this->actor->can('viewLastSeenAt', $user)) {
            $attributes += [
                'lastSeenAt' => $this->formatDate($user->last_seen_at)
            ];
        }

        if ($attributes['canEditCredentials'] || $this->actor->id === $user->id) {
            $attributes += [
                'isEmailConfirmed' => (bool) $user->is_email_confirmed,
                'email'            => $user->email
            ];
        }

        return $attributes;
    }
}
