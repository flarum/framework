<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

class CoreSettingTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setting('unknown_settings', 'a');
        $this->setting('forum_title', 'a');
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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
}
