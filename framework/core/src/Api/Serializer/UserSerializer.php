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

        $canEdit = $gate->allows('edit', $user);

        $attributes += [
            'joinTime'         => $this->formatDate($user->joined_at),
            'discussionCount'  => (int) $user->discussion_count,
            'commentCount'     => (int) $user->comment_count,
            'canEdit'          => $canEdit,
            'canDelete'        => $gate->allows('delete', $user),
        ];

        if ($user->getPreference('discloseOnline') || $this->actor->can('viewLastSeenAt', $user)) {
            $attributes += [
                'lastSeenAt' => $this->formatDate($user->last_seen_at)
            ];
        }

        if ($canEdit || $this->actor->id === $user->id) {
            $attributes += [
                'isEmailConfirmed' => (bool) $user->is_email_confirmed,
                'email'            => $user->email
            ];
        }

        return $attributes;
    }
}
