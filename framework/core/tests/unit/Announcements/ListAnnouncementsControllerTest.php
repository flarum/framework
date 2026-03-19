<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Announcements;

use Flarum\Announcements\AnnouncementsFetcher;
use Flarum\Api\Controller\ListAnnouncementsController;
use Flarum\Http\ActorReference;
use Flarum\Testing\unit\TestCase;
use Flarum\User\User;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Laminas\Diactoros\ServerRequest;
use Mockery as m;

class ListAnnouncementsControllerTest extends TestCase
{
    private CacheRepository $cache;
    private AnnouncementsFetcher $fetcher;
    private ListAnnouncementsController $controller;

    private array $stubAnnouncements = [[
        'id' => '1',
        'title' => 'Test',
        'slug' => 'test',
        'commentCount' => 0,
        'createdAt' => '2026-01-01T00:00:00+00:00',
        'isSticky' => false,
        'url' => 'https://discuss.flarum.org/d/test',
        'excerpt' => '',
        'authorName' => null,
        'avatarUrl' => null,
    ]];

    protected function setUp(): void
    {
        $this->cache = new CacheRepository(new ArrayStore());
        $this->fetcher = m::mock(AnnouncementsFetcher::class);
        $this->controller = new ListAnnouncementsController($this->cache, $this->fetcher);
    }

    private function adminRequest(array $queryParams = []): ServerRequest
    {
        $actor = m::mock(User::class);
        $actor->shouldReceive('assertAdmin')->andReturn(null);

        $actorRef = new ActorReference();
        $actorRef->setActor($actor);

        $request = (new ServerRequest([], [], '/api/flarum/announcements', 'GET'))
            ->withQueryParams($queryParams);

        return $request->withAttribute('actorReference', $actorRef);
    }

    public function test_returns_fetched_data(): void
    {
        $this->fetcher->shouldReceive('fetch')->once()->andReturn($this->stubAnnouncements);

        $response = $this->controller->handle($this->adminRequest([]));

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('1', $data[0]['id']);
    }

    public function test_caches_result_on_first_call(): void
    {
        $this->fetcher->shouldReceive('fetch')->once()->andReturn($this->stubAnnouncements);

        // Two requests — fetcher should only be called once
        $this->controller->handle($this->adminRequest([]));
        $this->controller->handle($this->adminRequest([]));
    }

    public function test_bust_refetches_and_updates_cache(): void
    {
        $fresh = array_merge($this->stubAnnouncements, [['id' => '2', 'title' => 'Fresh', 'slug' => 'fresh',
            'commentCount' => 0, 'createdAt' => '2026-01-01T00:00:00+00:00',
            'isSticky' => false, 'url' => 'https://discuss.flarum.org/d/fresh',
            'excerpt' => '', 'authorName' => null, 'avatarUrl' => null]]);

        $this->fetcher->shouldReceive('fetch')->once()->andReturn($fresh);

        $response = $this->controller->handle($this->adminRequest(['bust' => '1']));

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertCount(2, $data);

        // Verify cache was updated
        $this->assertEquals($fresh, $this->cache->get(ListAnnouncementsController::CACHE_KEY));
    }

    public function test_bust_falls_back_to_stale_cache_on_fetch_failure(): void
    {
        $stale = [['id' => '999', 'title' => 'Stale', 'slug' => 'stale',
            'commentCount' => 0, 'createdAt' => '2026-01-01T00:00:00+00:00',
            'isSticky' => false, 'url' => 'https://discuss.flarum.org/d/stale',
            'excerpt' => '', 'authorName' => null, 'avatarUrl' => null]];

        $this->cache->put(ListAnnouncementsController::CACHE_KEY, $stale, 3600);

        $this->fetcher->shouldReceive('fetch')->once()->andThrow(new \RuntimeException('discuss is down'));

        $response = $this->controller->handle($this->adminRequest(['bust' => '1']));

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('999', $data[0]['id']);
    }

    public function test_bust_returns_empty_array_when_fetch_fails_and_no_cache(): void
    {
        $this->fetcher->shouldReceive('fetch')->once()->andThrow(new \RuntimeException('discuss is down'));

        $response = $this->controller->handle($this->adminRequest(['bust' => '1']));

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEmpty($data);
    }
}
