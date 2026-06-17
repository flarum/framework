<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\middleware;

use Flarum\Api\Middleware\AddAssetsRevisionHeader;
use Flarum\Frontend\Compiler\VersionerInterface;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AssetsRevisionHeaderTest extends TestCase
{
    #[Test]
    public function api_responses_carry_the_assets_revision_header()
    {
        $response = $this->send(
            $this->request('GET', '/api')
        );

        $this->assertArrayHasKey(strtolower(AddAssetsRevisionHeader::HEADER_NAME), array_change_key_case($response->getHeaders()));

        $token = $response->getHeader(AddAssetsRevisionHeader::HEADER_NAME)[0] ?? '';
        $this->assertNotEmpty($token);
    }

    #[Test]
    public function the_token_reflects_the_versioner_so_it_changes_when_a_revision_changes()
    {
        $first = $this->send($this->request('GET', '/api'))
            ->getHeader(AddAssetsRevisionHeader::HEADER_NAME)[0];

        // Change a revision via the bound versioner (a custom versioner would be honoured the
        // same way, since the token is derived only from VersionerInterface::allRevisions()).
        $this->app()->getContainer()->make(VersionerInterface::class)->putRevision('forum.js', 'a-new-revision');

        $second = $this->send($this->request('GET', '/api'))
            ->getHeader(AddAssetsRevisionHeader::HEADER_NAME)[0];

        $this->assertNotEquals($first, $second);
    }
}
