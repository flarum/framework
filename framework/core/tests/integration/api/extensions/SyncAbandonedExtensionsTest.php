<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\extensions;

use Flarum\Extend;
use Flarum\Extension\AbandonedExtensionsFetcher;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class SyncAbandonedExtensionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        // Replace the real fetcher with a stub so tests never make real HTTP requests.
        $this->extend(
            (new Extend\ServiceProvider())->register(StubAbandonedExtensionsProvider::class)
        );

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    #[Test]
    public function guest_cannot_trigger_sync(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/extensions/abandoned/sync')
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function normal_user_cannot_trigger_sync(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/extensions/abandoned/sync', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
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

class StubAbandonedExtensionsFetcher extends AbandonedExtensionsFetcher
{
    public function __construct()
    {
    }

    public function sync(bool $notify = false, bool $manual = false): array
    {
        return ['count' => 2, 'new' => ['vendor/pkg-a']];
    }
}

class StubAbandonedExtensionsProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->instance(AbandonedExtensionsFetcher::class, new StubAbandonedExtensionsFetcher());
    }
}
