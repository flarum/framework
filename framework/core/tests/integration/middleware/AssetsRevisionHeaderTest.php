<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\middleware;

use Flarum\Api\Client;
use Flarum\Api\Middleware\AddAssetsRevisionHeader;
use Flarum\Frontend\Compiler\VersionerInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Guest;
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

    #[Test]
    public function in_process_api_requests_leave_dirty_assets_for_a_real_request_to_rebuild()
    {
        // Queue jobs render through the in-process client (realtime payloads,
        // notifications). Rebuilding the forum's JS there runs a worker out of
        // memory, from sources the worker booted with, and no browser reads the
        // header from those responses anyway.
        $settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);
        $settings->set('assets_dirty.forum', 1);

        $response = $this->app()->getContainer()->make(Client::class)->withActor(new Guest())->get('/');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $settings->get('assets_dirty.forum'));
        $this->assertFalse($response->hasHeader(AddAssetsRevisionHeader::HEADER_NAME));
    }
}
