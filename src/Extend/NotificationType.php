<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;
use Flarum\Core\Notifications\Notification;
use Flarum\Core\Users\User;
use Flarum\Api\Serializers\NotificationSerializer;
use ReflectionClass;

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
        $type = $class::getType();

        Notification::setSubjectModel($type, $class);

        User::addPreference(User::getNotificationPreferenceKey($type, 'alert'), 'boolval', in_array('alert', $this->enabled));

        if ((new ReflectionClass($class))->implementsInterface('Flarum\Core\Notifications\MailableBlueprint')) {
            User::addPreference(User::getNotificationPreferenceKey($type, 'email'), 'boolval', in_array('email', $this->enabled));
        }

        NotificationSerializer::setSubjectSerializer($type, $this->serializer);
    }
}
