<?php namespace Flarum\Core\Notifications;

use Flarum\Core\Notifications\Types\Notification;
use Flarum\Core\Models\Notification as NotificationModel;
use Flarum\Core\Models\User;
use Illuminate\Container\Container;

class Notifier
{
    protected $methods = [];

    protected $types = [];

    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function send(Notification $notification)
    {
        foreach ($this->methods as $method => $sender) {
            $sender = $this->container->make($sender);
            if ($notification->getRecipient()->shouldNotify($notification::getType(), $method) && $sender->compatibleWith($notification)) {
                $sender->send($notification);
            }
        }
    }

    public function registerMethod($name, $class)
    {
        $this->methods[$name] = $class;
    }

    public function registerType($class, $defaultPreferences = [])
    {
        $this->types[] = $class;

        NotificationModel::registerType($class);

        foreach ($this->methods as $method => $sender) {
            $sender = $this->container->make($sender);
            if ($sender->compatibleWith($class)) {
                User::registerPreference(User::notificationPreferenceKey($class::getType(), $method), 'boolval', array_get($defaultPreferences, $method, false));
            }
        }
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function getTypes()
    {
        return $this->types;
    }
}
