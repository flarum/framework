<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Extension;

use Flarum\Extension\AbandonedExtensionsFetcher;
use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

class AbandonedExtensionsFullMapTest extends TestCase
{
    private const LIST = [
        'vendor/old-a' => ['replacement' => 'vendor/new-a'],
        'vendor/not-installed' => ['replacement' => 'vendor/new-b'],
    ];

    private Repository $cache;

    /** @var list<string> */
    private array $requested = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new Repository(new ArrayStore());
    }

    /**
     * @param list<Response|callable> $responses
     */
    private function fetcher(array $responses, ?LoggerInterface $logger = null): AbandonedExtensionsFetcher
    {
        $extensions = $this->createStub(ExtensionManager::class);
        $extensions->method('getExtensions')->willReturn(new Collection([
            new Extension('/tmp/old-a', ['name' => 'vendor/old-a']),
        ]));

        $settings = $this->createStub(SettingsRepositoryInterface::class);
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create(function ($request, $options) use ($mock) {
            $this->requested[] = (string) $request->getUri();

            return $mock($request, $options);
        });

        return new AbandonedExtensionsFetcher(
            $extensions,
            $settings,
            new Client(['handler' => $handler]),
            $this->createStub(Queue::class),
            $this->createStub(TranslatorInterface::class),
            $logger ?? $this->createStub(LoggerInterface::class),
            $this->cache,
        );
    }

    #[Test]
    public function a_sync_keeps_the_whole_list_including_packages_that_are_not_installed(): void
    {
        $fetcher = $this->fetcher([new Response(200, [], json_encode(self::LIST))]);

        $fetcher->sync();

        $this->assertSame(self::LIST, $fetcher->getFullMap());
        $this->assertCount(1, $this->requested);
    }

    #[Test]
    public function the_list_is_fetched_once_when_nothing_is_cached(): void
    {
        $fetcher = $this->fetcher([new Response(200, [], json_encode(self::LIST))]);

        $this->assertSame(self::LIST, $fetcher->getFullMap());
        $this->assertSame(self::LIST, $fetcher->getFullMap());
        $this->assertCount(1, $this->requested);
    }

    #[Test]
    public function a_list_that_cannot_be_fetched_is_empty_logged_and_not_asked_for_again_straight_away(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with($this->stringContains('Could not fetch abandoned extensions list'));

        $fetcher = $this->fetcher([new Response(503)], $logger);

        $this->assertSame([], $fetcher->getFullMap());
        $this->assertSame([], $fetcher->getFullMap());
        $this->assertCount(1, $this->requested);
    }

    #[Test]
    public function nothing_is_fetched_when_asked_not_to(): void
    {
        $fetcher = $this->fetcher([]);

        $this->assertSame([], $fetcher->getFullMap(fetch: false));
        $this->assertSame([], $this->requested);
    }

    #[Test]
    public function a_failed_fetch_does_not_overwrite_a_sync_that_finished_while_it_waited(): void
    {
        $fetcher = $this->fetcher([function () {
            $this->cache->put(AbandonedExtensionsFetcher::LIST_CACHE_KEY, self::LIST, 60);

            return new Response(503);
        }]);

        $this->assertSame(self::LIST, $fetcher->getFullMap());
        $this->assertSame(self::LIST, $this->cache->get(AbandonedExtensionsFetcher::LIST_CACHE_KEY));
    }

    #[Test]
    public function a_store_that_cannot_be_written_does_not_fail_the_request(): void
    {
        $dir = sys_get_temp_dir().'/abandoned-ro-'.bin2hex(random_bytes(4));
        mkdir($dir, 0555);

        // FileStore warns when it cannot write; the warnings are expected here.
        set_error_handler(fn () => true, E_WARNING);

        try {
            $this->cache = new Repository(new FileStore(new Filesystem(), $dir.'/store'));

            $this->assertSame([], $this->fetcher([new Response(503)])->getFullMap());
            $this->assertSame(self::LIST, $this->fetcher([new Response(200, [], json_encode(self::LIST))])->getFullMap());
        } finally {
            restore_error_handler();
            chmod($dir, 0755);
            (new Filesystem())->deleteDirectory($dir);
        }
    }

    #[Test]
    public function a_sync_replaces_a_failed_fetch(): void
    {
        $fetcher = $this->fetcher([new Response(503), new Response(200, [], json_encode(self::LIST))]);

        $fetcher->getFullMap();
        $fetcher->sync();

        $this->assertSame(self::LIST, $fetcher->getFullMap());
    }
}
