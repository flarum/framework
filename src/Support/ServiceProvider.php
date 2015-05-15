<?php namespace Flarum\Support;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Core\Models\Notification;
use Flarum\Core\Models\User;
use Flarum\Core\Models\Post;
use Flarum\Core\Models\Permission;
use Closure;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    protected function forumAssets($assets)
    {
        $this->app['events']->listen('Flarum\Forum\Events\RenderView', function ($event) use ($assets) {
            $event->assets->addFile($assets);
        });
    }

    protected function postType($class)
    {
        Post::addType($class);
    }

    protected function discussionGambit($class)
    {
        $this->app['events']->listen('Flarum\Core\Events\RegisterDiscussionGambits', function ($event) use ($class) {
            $event->gambits->add($class);
        });
    }

    protected function formatter($name, $class, $priority = 0)
    {
        $this->app['flarum.formatter']->add($name, $class, $priority);
    }

    protected function notificationType($class, $defaultPreferences = [])
    {
        $notifier = $this->app['flarum.notifier'];

        $notifier->registerType($class);

        Notification::registerType($class);

        foreach ($notifier->getMethods() as $method => $sender) {
            if ($sender::compatibleWith($class)) {
                User::registerPreference(User::notificationPreferenceKey($class::getType(), $method), 'boolval', array_get($defaultPreferences, $method, false));
            }
        }
    }

    protected function relationship($parent, $type, $name, $child = null)
    {
        $parent::addRelationship($name, function ($model) use ($type, $name, $child) {
            if ($type instanceof Closure) {
                return $type($model);
            } elseif ($type === 'belongsTo') {
                return $model->belongsTo($child, null, null, $name);
            } else {
                // @todo
            }
        });
    }

    protected function serializeRelationship($parent, $type, $name, $child = null)
    {
        $parent::addRelationship($name, function ($serializer) use ($type, $name, $child) {
            if ($type instanceof Closure) {
                return $type();
            } else {
                return $serializer->$type($child, $name);
            }
        });
    }

    protected function serializeAttributes($serializer, Closure $callback)
    {
        $this->app['events']->listen('Flarum\Api\Events\SerializeAttributes', function ($event) use ($serializer, $callback) {
            if ($event->serializer instanceof $serializer) {
                $callback($event->attributes, $event->model, $event->serializer);
            }
        });
    }

    protected function permission($permission)
    {
        Permission::addPermission($permission);
    }
}
