<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\integrations;

use Flarum\Gdpr\Models\Export;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\User\User;
use Illuminate\Contracts\Filesystem\Factory;
use PHPUnit\Framework\Attributes\Test;

class GdprTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-gdpr');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            'gdpr_exports' => [],
        ]);
    }

    protected function tearDown(): void
    {
        $disk = $this->app()->getContainer()->make(Factory::class)->disk('gdpr-export');
        $disk->delete($disk->allFiles());

        parent::tearDown();
    }

    #[Test]
    public function request_data_export_exports_the_members_own_data(): void
    {
        $requested = $this->toolOutput($this->callTool('request_data_export', [], $this->bearer($this->developerToken(2))));

        $this->assertSame(['requested' => true], $requested);
        // The test queue runs the job straight away.
        $this->assertTrue(Export::query()->where('user_id', 2)->exists());
    }

    #[Test]
    public function request_data_export_is_not_offered_to_guests(): void
    {
        $this->prepareDatabase(['group_permission' => [['group_id' => 2, 'permission' => 'mcp.use']]]);

        $this->assertNotContains('request_data_export', $this->toolNames($this->modern('tools/list')));
    }
}
