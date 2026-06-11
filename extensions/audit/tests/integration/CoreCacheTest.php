<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

class CoreCacheTest extends TestCase
{
    /**
     * @test
     */
    public function web_cache_clear()
    {
        $this->sendSuccessfulRequest('DELETE', '/api/cache', [], 204);

        $this->assertLogExists('cache_cleared');
    }
}
