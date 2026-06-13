<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration;

use Flarum\Realtime\Websocket\Console\HaltCommand;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Cache\Repository;
use PHPUnit\Framework\Attributes\Test;

/**
 * The websocket server reads its settings once, so changing a realtime setting
 * must signal it to restart. The listener writes the halt key (the same one
 * `realtime:halt` uses) when any `flarum-realtime.*` setting is saved — and only
 * then, so saving unrelated settings does not needlessly drop live connections.
 */
class RestartOnSettingChangeTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime');
    }

    private function cache(): Repository
    {
        return $this->app()->getContainer()->make(Repository::class);
    }

    private function saveSetting(string $key, string $value): int
    {
        return $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => [$key => $value],
            ])
        )->getStatusCode();
    }

    #[Test]
    public function saving_a_realtime_setting_signals_a_restart(): void
    {
        $this->cache()->forget(HaltCommand::KEY);

        $status = $this->saveSetting('flarum-realtime.index-typing-indicator-restricted', '1');

        $this->assertEquals(204, $status);
        $this->assertTrue($this->cache()->has(HaltCommand::KEY), 'A realtime setting change should signal the websocket server to restart.');
    }

    #[Test]
    public function saving_an_unrelated_setting_does_not_signal_a_restart(): void
    {
        $this->cache()->forget(HaltCommand::KEY);

        $status = $this->saveSetting('some-other-extension.some_setting', 'value');

        $this->assertEquals(204, $status);
        $this->assertFalse($this->cache()->has(HaltCommand::KEY), 'A non-realtime setting change must not restart the websocket server.');
    }
}
