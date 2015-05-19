<?php namespace Flarum\Core\Notifications;

use Flarum\Core\Notifications\Types\Notification;
use Flarum\Core\Notifications\Senders\RetractableSender;
use Flarum\Core\Models\Notification as NotificationModel;
use Flarum\Core\Models\User;
use Illuminate\Container\Container;
use Closure;

class Notifier
{
    protected $methods = [];

    protected $types = [];

    protected $onePerUser = false;

    protected $sentTo = [];

    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function send(Notification $notification, array $users)
    {
        foreach ($users as $user) {
            if ($this->onePerUser && in_array($user->id, $this->sentTo)) {
                continue;
            }

            foreach ($this->methods as $method => $sender) {
                $sender = $this->container->make($sender);

                if ($sender::compatibleWith($notification) &&
                    $user->shouldNotify($notification::getType(), $method)) {
                    $sender->send($notification, $user);
                    $this->sentTo[] = $user->id;
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

    public function onePerUser(Closure $callback)
    {
        $this->sentTo = [];
        $this->onePerUser = true;

        $callback();

        $this->onePerUser = false;
    }
}
