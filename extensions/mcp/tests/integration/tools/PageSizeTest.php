<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\tools;

use Flarum\Api\Endpoint\Index;
use Flarum\Api\Resource\GroupResource;
use Flarum\Extend;
use Flarum\Mcp\Testing\McpTestCase;
use PHPUnit\Framework\Attributes\Test;

class PageSizeTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Extend\ApiResource(GroupResource::class))
                ->endpoint(Index::class, fn (Index $endpoint) => $endpoint->maxLimit(2))
        );
    }

    #[Test]
    public function describes_the_page_size_a_type_with_a_smaller_largest_page_gets(): void
    {
        $auth = $this->bearer($this->developerToken(1));

        $groups = $this->toolOutput($this->callTool('describe_api', ['type' => 'groups'], $auth));
        $list = $this->toolOutput($this->callTool('list_resources', ['type' => 'groups'], $auth));

        $this->assertSame(['default' => 2, 'max' => 2], $groups['pageLimit'] ?? null);
        $this->assertSame(2, $list['meta']['page']['limit'] ?? null);
    }
}
