<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializers;

class NotificationSerializer extends Serializer
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
     */
    protected function getDefaultAttributes($notification)
    {
        return [
            'id'          => (int) $notification->id,
            'contentType' => $notification->type,
            'content'     => $notification->data,
            'time'        => $notification->time->toRFC3339String(),
            'isRead'      => (bool) $notification->is_read,
            'unreadCount' => $notification->unread_count
        ];
    }

    /**
     * @return callable
     */
    public function user()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserBasicSerializer');
    }

    /**
     * @return callable
     */
    public function sender()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserBasicSerializer');
    }

    /**
     * @return callable
     */
    public function subject()
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
