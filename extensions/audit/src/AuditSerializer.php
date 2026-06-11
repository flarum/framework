<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Api\Serializer\BasicPostSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Api\Serializer\TagSerializer;
use Tobscure\JsonApi\Relationship;

class AuditSerializer extends AbstractSerializer
{
    protected $type = 'flarum-audit';

    /**
     * @param AuditLog $log
     * @return array
     */
    protected function getDefaultAttributes($log): array
    {
        return [
            'actorId' => $log->actor_id,
            'client' => $log->client,
            'ipAddress' => $this->ipAddress($log),
            'action' => $log->action,
            'payload' => $log->payload,
            'createdAt' => $this->formatDate($log->created_at),
        ];
    }

    protected function ipAddress(AuditLog $log)
    {
        if (! $this->actor->hasPermission('flarum-audit.view')) {
            /** @var SettingsRepositoryInterface $settings */
            $settings = resolve(SettingsRepositoryInterface::class);

            if (! $settings->get('flarum-audit.limitedIpAddress')) {
                return null;
            }
        }

        return $log->ip_address;
    }

    public function actor($log): ?Relationship
    {
        return $this->hasOne($log, BasicUserSerializer::class);
    }

    public function discussion($log): ?Relationship
    {
        return $this->hasOne($log, BasicDiscussionSerializer::class);
    }

    public function newDiscussion($log): ?Relationship
    {
        return $this->hasOne($log, BasicDiscussionSerializer::class);
    }

    public function post($log): ?Relationship
    {
        return $this->hasOne($log, BasicPostSerializer::class);
    }

    public function tag($log): ?Relationship
    {
        return $this->hasOne($log, TagSerializer::class);
    }

    public function user($log): ?Relationship
    {
        return $this->hasOne($log, BasicUserSerializer::class);
    }
}
