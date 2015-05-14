<?php namespace Flarum\Core\Notifications;

use Flarum\Core\Notifications\Types\Notification;
use Flarum\Core\Notifications\Senders\RetractableSender;
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

    public function send(Notification $notification, array $users)
    {
        foreach ($this->methods as $method => $sender) {
            $sender = $this->container->make($sender);

            if ($sender::compatibleWith($notification)) {
                foreach ($users as $user) {
                    if ($user->shouldNotify($notification::getType(), $method)) {
                        $sender->send($notification, $user);
                    }
                }
            }
        }
    }

    public function retract(Notification $notification)
    {
        foreach ($this->methods as $method => $sender) {
            $sender = $this->container->make($sender);

            if ($sender instanceof RetractableSender && $sender::compatibleWith($notification)) {
                $sender->retract($notification);
            }
        }
    }

    public function registerMethod($name, $class)
    {
        $this->methods[$name] = $class;
    }

    public function registerType($class)
    {
        $this->types[] = $class;
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
