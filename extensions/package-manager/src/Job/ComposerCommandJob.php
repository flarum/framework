<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Job;

use Flarum\Queue\AbstractJob;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Psr\Log\LoggerInterface;

/**
 * A job the Extension Manager queued before it kept its runs under storage/.
 *
 * Kept so that one still waiting in the queue when a forum updates is taken off
 * it quietly instead of failing in the worker. It does nothing: the command it
 * carried and the task row it reported to no longer exist. Remove in 3.0.
 *
 * The properties are untyped on purpose: the command they held was an instance
 * of a class that is gone, which unserializes as __PHP_Incomplete_Class.
 */
class ComposerCommandJob extends AbstractJob implements ShouldBeUnique
{
    /** @var mixed */
    protected $command;

    /** @var mixed */
    protected $phpVersion;

    public function handle(LoggerInterface $logger): void
    {
        $logger->info('[flarum/extension-manager] Discarded a queued Composer command left from an earlier version of the Extension Manager. Run it again from the admin panel if it is still needed.');
    }
}
