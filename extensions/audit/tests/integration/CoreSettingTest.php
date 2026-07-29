<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use PHPUnit\Framework\Attributes\Test;

class CoreSettingTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setting('unknown_settings', 'a');
        $this->setting('forum_title', 'a');
    }

    #[Test]
    public function unknown_setting()
    {
        $this->sendSuccessfulRequest('POST', '/api/settings', [
            'json' => [
                'unknown_settings' => 'b',
            ],
        ], 204);

        $this->assertLogExists('setting_changed', [
            'key' => 'unknown_settings',
        ]);
    }

    #[Test]
    public function whitelisted_setting()
    {
        $this->sendSuccessfulRequest('POST', '/api/settings', [
            'json' => [
                'forum_title' => 'b',
            ],
        ], 204);

        $this->assertLogExists('setting_changed', [
            'key' => 'forum_title',
            'old_value' => 'a',
            'new_value' => 'b',
        ]);
    }

    #[Test]
    public function settings_reset()
    {
        $this->sendSuccessfulRequest('DELETE', '/api/settings', [
            'json' => [
                'extensionId' => '',
                'keys' => ['forum_title'],
            ],
        ], 204);

        $this->assertLogExists('settings_reset', [
            'extension' => '',
            'keys' => ['forum_title'],
        ]);
    }
}
