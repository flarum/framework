<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Notification\Notification;
use InvalidArgumentException;
use Tobscure\JsonApi\Relationship;

class NotificationSerializer extends AbstractSerializer
{
    protected $type = 'notifications';

    /**
     * A map of notification types (key) to the serializer that should be used
     * to output the notification's subject (value).
     */
    protected static array $subjectSerializers = [];

    /**
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes(object|array $model): array
    {
        if (! ($model instanceof Notification)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Notification::class
            );
        }

        return [
            'contentType' => $model->type,
            'content'     => $model->data,
            'createdAt'   => $this->formatDate($model->created_at),
            'isRead'      => (bool) $model->read_at
        ];
    }

    protected function user(Notification $notification): ?Relationship
    {
        return $this->hasOne($notification, BasicUserSerializer::class);
    }

    protected function fromUser(Notification $notification): ?Relationship
    {
        return $this->hasOne($notification, BasicUserSerializer::class);
    }

    protected function subject(Notification $notification): ?Relationship
    {
        return $this->hasOne($notification, function (Notification $notification) {
            return static::$subjectSerializers[$notification->type];
        });
    }

    public static function setSubjectSerializer(string $type, string $serializer): void
    {
        static::$subjectSerializers[$type] = $serializer;
    }
}
