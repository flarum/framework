<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extend\LifecycleInterface;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

/**
 * Takes care of logging the activation of the extension itself.
 * This is necessary to keep a trace of when the extension was inactive in case it's temporarily disabled.
 */
class LogSelfEnabled implements ExtenderInterface, LifecycleInterface
{
    public function onEnable(Container $container, Extension $extension): void
    {
        // In integration tests, the lifecycle events are triggered outside the transaction
        // which results in unwanted entries in the database.
        if (AuditLogger::$testMode) {
            return;
        }

        // Unfortunately we can't set the actor or client here.
        AuditLogger::log('extension.enabled', [
            'package' => $extension->name,
        ]);
    }

    public function onDisable(Container $container, Extension $extension): void
    {
        // Nothing to do. Already logged by the event listener.
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        // Nothing to do. But we can't have LifecycleInterface extenders without ExtenderInterface...
    }
}
