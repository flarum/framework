<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Core\Notification;
use InvalidArgumentException;

class NotificationSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'notifications';

    /**
     * A map of notification types (key) to the serializer that should be used
     * to output the notification's subject (value).
     *
     * @var array
     */
    protected static $subjectSerializers = [];

    /**
     * {@inheritdoc}
     *
     * @param \Flarum\Core\Notification $notification
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($notification)
    {
        if (! ($notification instanceof Notification)) {
            throw new InvalidArgumentException(get_class($this)
                . ' can only serialize instances of ' . Notification::class);
        }

        return [
            'id'          => (int) $notification->id,
            'contentType' => $notification->type,
            'content'     => $notification->data,
            'time'        => $this->formatDate($notification->time),
            'isRead'      => (bool) $notification->is_read
        ];
    }

    /**
     * @return \Flarum\Api\Relationship\HasOneBuilder
     */
    protected function user()
    {
        return $this->hasOne('Flarum\Api\Serializer\UserBasicSerializer');
    }

    /**
     * @return \Flarum\Api\Relationship\HasOneBuilder
     */
    protected function sender()
    {
        return $this->hasOne('Flarum\Api\Serializer\UserBasicSerializer');
    }

    /**
     * @return \Flarum\Api\Relationship\HasOneBuilder
     */
    protected function subject()
    {
        return $this->hasOne(function ($notification) {
            return static::$subjectSerializers[$notification->type];
        });
    }

    /**
     * @param $type
     * @param $serializer
     */
    public static function setSubjectSerializer($type, $serializer)
    {
        static::$subjectSerializers[$type] = $serializer;
    }
}
