<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\announcements;

use Flarum\Announcements\AnnouncementsFetcher;
use Flarum\Extend;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use PHPUnit\Framework\Attributes\Test;

class ListTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        // Replace the real fetcher with a stub so tests never hit discuss.flarum.org
        $this->extend(
            (new Extend\ServiceProvider())->register(StubAnnouncementsProvider::class)
        );

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    #[Test]
    public function guest_cannot_list_announcements(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/flarum/announcements')
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function non_admin_cannot_list_announcements(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/flarum/announcements', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function admin_can_list_announcements(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/flarum/announcements', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('title', $data[0]);
        $this->assertArrayHasKey('url', $data[0]);
    }

    #[Test]
    public function bust_param_forces_cache_refresh(): void
    {
        // Prime the cache
        $this->send(
            $this->request('GET', '/api/flarum/announcements', ['authenticatedAs' => 1])
        );

        // Bust it — should still return valid data
        $response = $this->send(
            $this->request('GET', '/api/flarum/announcements?bust=1', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertNotEmpty($data);
    }

    #[Test]
    public function bust_returns_empty_array_when_fetcher_fails_and_no_cache(): void
    {
        // Swap the fetcher to one that always fails, before app boots
        $this->extend(
            (new Extend\ServiceProvider())->register(FailingAnnouncementsProvider::class)
        );

        $response = $this->send(
            $this->request('GET', '/api/flarum/announcements?bust=1', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }
}

class StubAnnouncementsFetcher extends AnnouncementsFetcher
{
    public function fetch(): array
    {
        return [[
            'id' => '1',
            'title' => 'Test Announcement',
            'slug' => 'test-announcement',
            'commentCount' => 3,
            'createdAt' => '2026-01-01T00:00:00+00:00',
            'isSticky' => false,
            'url' => 'https://discuss.flarum.org/d/test-announcement',
            'excerpt' => 'This is a test.',
            'authorName' => 'IanM',
            'avatarUrl' => null,
        ]];
    }
}

class FailingAnnouncementsFetcher extends AnnouncementsFetcher
{
    public function fetch(): array
    {
        throw new \RuntimeException('discuss.flarum.org is unreachable');
    }
}

class StubAnnouncementsProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->bind(AnnouncementsFetcher::class, fn (Container $container) => new StubAnnouncementsFetcher(
            $container->make(\Flarum\Foundation\ApplicationInfoProvider::class)
        ));
    }
}

class FailingAnnouncementsProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->bind(AnnouncementsFetcher::class, fn (Container $container) => new FailingAnnouncementsFetcher(
            $container->make(\Flarum\Foundation\ApplicationInfoProvider::class)
        ));
    }
}
