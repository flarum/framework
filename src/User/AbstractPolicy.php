<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

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
     * @return bool|void
     */
    public function getPermission(GetPermission $event)
    {
        if (! $event->model instanceof $this->model && $event->model !== $this->model) {
            return;
        }

        if (method_exists($this, $event->ability)) {
            $result = call_user_func_array([$this, $event->ability], [$event->actor, $event->model]);

            if (! is_null($result)) {
                return $result;
            }
        }

        if (method_exists($this, 'can')) {
            return call_user_func_array([$this, 'can'], [$event->actor, $event->ability, $event->model]);
        }
    }

    /**
     * @param ScopeModelVisibility $event
     */
    public function scopeModelVisibility(ScopeModelVisibility $event)
    {
        if ($event->query->getModel() instanceof $this->model) {
            if (substr($event->ability, 0, 4) === 'view') {
                $method = 'find'.substr($event->ability, 4);

                if (method_exists($this, $method)) {
                    call_user_func_array([$this, $method], [$event->actor, $event->query]);
                }
            } elseif (method_exists($this, 'findWithPermission')) {
                call_user_func_array([$this, 'findWithPermission'], [$event->actor, $event->query, $event->ability]);
            }
        }
    }
}
