<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;
use Flarum\Core\Models\Notification;
use Flarum\Core\Models\User;
use Flarum\Api\Serializers\NotificationSerializer;

class NotificationType implements ExtenderInterface
{
    protected $class;

    protected $serializer;

    protected $enabled = [];

    public function __construct($class)
    {
        $this->class = $class;
    }

    public function subjectSerializer($serializer)
    {
        $this->serializer = $serializer;

        return $this;
    }

    public function enableByDefault($method)
    {
        $this->enabled[] = $method;

        return $this;
    }

    public function extend(Container $container)
    {
        $class = $this->class;

        Notification::registerType($class);

        User::registerPreference(User::notificationPreferenceKey($class::getType(), 'alert'), 'boolval', in_array('alert', $this->enabled));

        if ($class::isEmailable()) {
            User::registerPreference(User::notificationPreferenceKey($class::getType(), 'email'), 'boolval', in_array('email', $this->enabled));
        }

        NotificationSerializer::$subjects[$class::getType()] = $this->serializer;
    }
}
