<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Access;

use Flarum\Event\GetPermission;
use Flarum\Event\ScopeModelVisibility;
use Illuminate\Contracts\Events\Dispatcher;

abstract class AbstractPolicy
{
    /**
     * @var string
     */
    protected $model;

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(GetPermission::class, [$this, 'getPermission']);
        $events->listen(ScopeModelVisibility::class, [$this, 'scopeModelVisibility']);
    }

    /**
     * @param GetPermission $event
     * @return bool|null
     */
    public function getPermission(GetPermission $event)
    {
        if (isset($event->arguments[0]) && $event->arguments[0] instanceof $this->model) {
            if (method_exists($this, 'before')) {
                $arguments = array_merge([$event->actor, $event->ability], $event->arguments);

                $result = call_user_func_array([$this, 'before'], $arguments);

                if (! is_null($result)) {
                    return $result;
                }
            }

            if (method_exists($this, $event->ability)) {
                $arguments = array_merge([$event->actor], $event->arguments);

                return call_user_func_array([$this, $event->ability], $arguments);
            }
        }
    }

    /**
     * @param ScopeModelVisibility $event
     */
    public function scopeModelVisibility(ScopeModelVisibility $event)
    {
        if ($event->model instanceof $this->model && method_exists($this, 'find')) {
            call_user_func_array([$this, 'find'], [$event->actor, $event->query]);
        }
    }
}
