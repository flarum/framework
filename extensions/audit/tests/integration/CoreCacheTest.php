<?php

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
