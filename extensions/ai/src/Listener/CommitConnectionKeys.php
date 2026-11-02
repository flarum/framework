<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Listener;

use Flarum\Ai\ConnectionKeys;
use Flarum\Settings\Event\Saved;

/**
 * Writes the key changes {@see PrepareSettings} staged, now that every
 * setting in the save has been written.
 *
 * @internal
 */
class CommitConnectionKeys
{
    public function __construct(
        protected ConnectionKeys $keys
    ) {
    }

    public function handle(Saved $event): void
    {
        $this->keys->commit();
    }
}
