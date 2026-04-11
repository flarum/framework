<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\extensions;

use Flarum\Extension\AbandonedExtensionsFetcher;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class SyncAbandonedExtensionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
        ]);

        // Bind a fake fetcher that doesn't make real HTTP requests.
        $this->app()->getContainer()->instance(
            AbandonedExtensionsFetcher::class,
            new class extends AbandonedExtensionsFetcher {
                public function __construct()
                {
                }

                public function sync(bool $notify = false, bool $manual = false): array
                {
                    return ['count' => 2, 'new' => ['vendor/pkg-a']];
                }
            }
        );
    }

    /**
     * @test
     */
    public function guest_cannot_trigger_sync(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/extensions/abandoned/sync')
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function normal_user_cannot_trigger_sync(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/extensions/abandoned/sync', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function admin_can_trigger_sync(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/extensions/abandoned/sync', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('count', $body);
        $this->assertIsInt($body['count']);
    }
}
