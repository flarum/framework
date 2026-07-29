<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extension;

use Flarum\Testing\integration\ConsoleTestCase;
use PHPUnit\Framework\Attributes\Test;

class ScheduledAbandonedSyncTest extends ConsoleTestCase
{
    /**
     * The abandoned-extensions sync command is registered by core's ExtensionServiceProvider.
     * It must end up in the scheduler so the abandoned list is refreshed automatically.
     *
     * This is a regression test: the registration was previously done in the provider's boot()
     * method, which ran after ConsoleServiceProvider::boot() had already consumed the
     * `flarum.console.scheduled` array, so the task was silently dropped and never scheduled.
     */
    #[Test]
    public function abandoned_sync_command_is_scheduled()
    {
        $output = $this->runCommand([
            'command' => 'schedule:list',
        ]);

        $this->assertStringContainsString('extensions:sync-abandoned', $output);
    }
}
