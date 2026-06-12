<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Extend;

use Flarum\Audit\AuditLogger;
use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Public extender for declaring audit log integrations.
 *
 * Any extension can add audit logging for its own events by instantiating this
 * extender in its `extend.php`. Wrap it in a `Conditional` so it is a no-op when
 * the audit extension is not enabled:
 *
 *     (new Flarum\Extend\Conditional())
 *         ->whenExtensionEnabled('flarum-audit', function () {
 *             return [
 *                 (new Flarum\Audit\Extend\Audit())
 *                     ->listen(IPWasBanned::class, 'fof_ban_ips.banned', function ($event) {
 *                         return ['ip' => $event->bannedIP->address];
 *                     }),
 *             ];
 *         }),
 *
 * Actions are grouped in the admin settings by the extension that registered them.
 * The group is auto-detected from the consuming extension's id; use {@see group()}
 * to set it explicitly (e.g. when audit declares core or first-party integrations itself).
 */
class Audit implements ExtenderInterface
{
    /**
     * Action strings to expose in the admin settings.
     *
     * @var string[]
     */
    protected array $actions = [];

    /**
     * Event listeners that produce a payload.
     *
     * @var array<array{0: string, 1: string, 2: callable}>
     */
    protected array $listeners = [];

    /**
     * Raw callbacks for advanced integrations, deferred until the application has booted.
     *
     * @var callable[]
     */
    protected array $callbacks = [];

    /**
     * Explicit admin grouping key. Null means auto-detect from the consuming extension.
     */
    protected ?string $group = null;

    /**
     * Whether the group was explicitly set (so we can allow `null`/`core` overrides).
     */
    protected bool $groupSet = false;

    /**
     * Force the admin grouping key. Omit to auto-detect from the consuming extension's id
     * (the common third-party case). Pass null to group under core.
     *
     * @param string|null $group
     * @return self
     */
    public function group(?string $group): self
    {
        $this->group = $group;
        $this->groupSet = true;

        return $this;
    }

    /**
     * Declare loggable action strings so they appear in the admin settings, without
     * binding an event listener. Useful for actions logged from middleware, console
     * commands or lifecycle hooks rather than events.
     *
     * @param string ...$actions
     * @return self
     */
    public function register(string ...$actions): self
    {
        array_push($this->actions, ...$actions);

        return $this;
    }

    /**
     * Listen to an event and log the given action. The callback receives the event
     * instance and must return the payload array to store, or null to skip logging.
     * The action is automatically registered for the admin settings.
     *
     * @param string $event The event class name.
     * @param string $action The action string to log.
     * @param callable $payload A callback `fn (object $event): ?array`.
     * @return self
     */
    public function listen(string $event, string $action, callable $payload): self
    {
        $this->listeners[] = [$event, $action, $payload];

        if (! in_array($action, $this->actions, true)) {
            $this->actions[] = $action;
        }

        return $this;
    }

    /**
     * Escape hatch for advanced integrations that can't be expressed as a simple event
     * listener (e.g. Eloquent model events or query builder macros). The callback is
     * invoked once the application has booted and receives the container. It is responsible
     * for calling {@see AuditLogger::log()} itself.
     *
     * If the callback is an object exposing a public static `$actions` array, those action
     * strings are registered automatically — so an integration class declares its action
     * vocabulary once, alongside its logic, and it stays visible in the admin settings and
     * search autocomplete without a separate {@see register()} call.
     *
     * @param callable $callback A callback `fn (Container $container): void`.
     * @return self
     */
    public function using(callable $callback): self
    {
        $this->callbacks[] = $callback;

        // Auto-register the integration's declared action vocabulary, if it exposes a public
        // static `$actions` array. Read via reflection so static analysis stays happy with the
        // dynamic property access on an arbitrary callable.
        if (is_object($callback)) {
            $class = new \ReflectionClass($callback);

            if ($class->hasProperty('actions')) {
                $property = $class->getProperty('actions');

                if ($property->isStatic() && $property->isPublic()) {
                    $actions = $property->getValue();

                    if (is_array($actions)) {
                        $this->register(...$actions);
                    }
                }
            }
        }

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        // Attribution: explicit group() wins; else the consuming extension's id; else core.
        $group = $this->groupSet ? $this->group : ($extension ? $extension->getId() : null);

        AuditLogger::register($group, ...$this->actions);

        $listeners = $this->listeners;
        $callbacks = $this->callbacks;

        $container->make('flarum')->booted(function () use ($container, $listeners, $callbacks) {
            $events = $container->make(Dispatcher::class);

            foreach ($listeners as [$event, $action, $payload]) {
                $events->listen($event, function ($event) use ($action, $payload) {
                    $result = $payload($event);

                    if ($result !== null) {
                        AuditLogger::log($action, $result);
                    }
                });
            }

            foreach ($callbacks as $callback) {
                $callback($container);
            }
        });
    }
}
