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
use Illuminate\Database\Eloquent\Builder;

abstract class AbstractPolicy
{
    /**
     * @var string
     */
    protected $model;

    public static $ALLOW = 'ALLOW';
    public static $DENY = 'DENY';
    public static $FORCE_ALLOW = 'FORCE_ALLOW';
    public static $FORCE_DENY = 'FORCE_DENY';

    protected function allow()
    {
        return static::$ALLOW;
    }

    protected function deny()
    {
        return static::$DENY;
    }

    protected function forceAllow()
    {
        return static::$FORCE_ALLOW;
    }

    protected function forceDeny()
    {
        return static::$FORCE_DENY;
    }

    /**
     * Check if a user has an ability on an instance of a model.
     * Whatever method this calls should return one of:
     * - $this->allow();
     * - $this->deny();
     * - $this->forceAllow();
     * - $this->forceDeny();
     * In evaluation, forceDeny > forceAllow > deny > allow.
     *
     * @param User $actor
     * @param $ability
     * @param mixed $instance
     */
    public function checkAbility(User $actor, $ability, $instance)
    {
        if (! $instance instanceof $this->model) {
            return;
        }

        // If a specific method for this ability is defined,
        // call that and return any non-null results
        if (method_exists($this, $ability)) {
            $result = call_user_func_array([$this, $ability], [$actor, $instance]);

            if (! is_null($result)) {
                return $result;
            }
        }

        // If a "total access" method is defined, try that.
        if (method_exists($this, 'can')) {
            return call_user_func_array([$this, 'can'], [$actor, $ability, $instance]);
        }
    }

    public function scopeQueryListener(ScopeModelVisibility $event)
    {
        if ($event->query->getModel() instanceof $this->model) {
            if ($event->ability == 'view' && method_exists($this, 'scopeQuery')) {
                call_user_func_array([$this, 'scopeQuery'], [$event->actor, $event->query]);
            } elseif (method_exists($this, 'scopeQueryPerAbility')) {
                call_user_func_array([$this, 'scopeQuery'], [$event->actor, $event->query, $event->ability]);
            }
        }
    }

    /**
     * @deprecated
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(GetPermission::class, [$this, 'getPermission']);
        $events->listen(ScopeModelVisibility::class, [$this, 'scopeModelVisibility']);
    }

    /**
     * @deprecated in favor of checkAbility
     */
    public function getPermission(GetPermission $event)
    {
        if (! $event->model instanceof $this->model) {
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
     * @deprecated
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
