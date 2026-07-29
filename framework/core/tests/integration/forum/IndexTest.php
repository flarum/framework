<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class IndexTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->prepareDatabase([
            User::class => [
                $this->normalUser()
            ]
        ]);
    }

    #[Test]
    public function guest_not_serialized_by_current_user_serializer()
    {
        $response = $this->send(
            $this->request('GET', '/')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringNotContainsString('preferences', $response->getBody()->getContents());
    }

    #[Test]
    public function user_serialized_by_current_user_serializer()
    {
        $response = $this->send(
            $this->request('GET', '/', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('preferences', $response->getBody()->getContents());
    }

    #[Test]
    public function index_renders_with_unknown_query_parameters()
    {
        // Social/ad platforms append tracking params (fbclid, gclid, ...) to
        // shared links. The frontend forwards the query string into the internal
        // API document request; those params must not 400 the page even though
        // the JSON:API spec requires rejecting them on external API requests.
        $response = $this->send(
            $this->request('GET', '/')->withQueryParams(['fbclid' => 'abc123', 'gclid' => 'xyz'])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function external_api_still_rejects_unknown_query_parameters()
    {
        // The fix must not relax validation for real external API consumers,
        // which the JSON:API spec requires to receive a 400 for an unknown
        // (all-lowercase, non-reserved) query parameter.
        $response = $this->send(
            $this->request('GET', '/api/discussions')->withQueryParams(['fbclid' => 'abc123'])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }
}
