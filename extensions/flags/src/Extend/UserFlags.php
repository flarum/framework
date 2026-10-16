<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Flags\UserFlagger;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

/** Register event-driven account reports from another server-side extension. */
class UserFlags implements ExtenderInterface
{
    protected array $listeners = [];

    /**
     * The target callback returns a persisted User, or null to skip the event.
     * The optional detail callback returns explanatory text for moderators.
     */
    public function on(string $event, callable $target, string $reason, ?callable $detail = null): self
    {
        $this->listeners[] = compact('event', 'target', 'reason', 'detail');

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $type = $extension?->getId() ?? 'system';

        foreach ($this->listeners as $listener) {
            $container->make(Dispatcher::class)->listen($listener['event'], function (object $event) use ($container, $listener, $type) {
                $target = ($listener['target'])($event);

                if ($target instanceof User) {
                    $container->make(UserFlagger::class)->flag(
                        $target,
                        $type,
                        $listener['reason'],
                        $listener['detail'] ? ($listener['detail'])($event) : null,
                    );
                }
            });
        }
    }
}
