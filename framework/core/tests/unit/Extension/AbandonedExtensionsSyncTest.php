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
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use RuntimeException;

class AbandonedExtensionsSyncTest extends TestCase
{
    private SettingsRepositoryInterface $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = new class implements SettingsRepositoryInterface {
            private array $values = [];

            public function all(): array
            {
                return $this->values;
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->values[$key] ?? $default;
            }

            public function set(string $key, mixed $value): void
            {
                $this->values[$key] = $value;
            }

            public function delete(string $keyLike): void
            {
                unset($this->values[$keyLike]);
            }
        };
    }

    /**
     * A fetcher for a forum with `vendor/old-a` installed, which the upstream
     * list flags as abandoned on every sync.
     */
    private function fetcher(?LoggerInterface $logger = null, int $syncs = 2): RecordingAbandonedExtensionsFetcher
    {
        $extensions = $this->createStub(ExtensionManager::class);
        $extensions->method('getExtensions')->willReturn(new Collection([
            new Extension('/tmp/old-a', ['name' => 'vendor/old-a']),
        ]));

        $responses = array_fill(0, $syncs, new Response(200, [], json_encode([
            'vendor/old-a' => ['replacement' => 'vendor/new-a'],
            'vendor/not-installed' => [],
        ])));

        return new RecordingAbandonedExtensionsFetcher(
            $extensions,
            $this->settings,
            new Client(['handler' => HandlerStack::create(new MockHandler($responses))]),
            $this->createStub(Queue::class),
            $this->createStub(TranslatorInterface::class),
            $logger ?? $this->createStub(LoggerInterface::class),
        );
    }

    #[Test]
    public function a_failed_fetch_is_logged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with($this->stringContains('Could not fetch abandoned extensions list'));

        $extensions = $this->createStub(ExtensionManager::class);
        $fetcher = new RecordingAbandonedExtensionsFetcher(
            $extensions,
            $this->settings,
            new Client(['handler' => HandlerStack::create(new MockHandler([new Response(503)]))]),
            $this->createStub(Queue::class),
            $this->createStub(TranslatorInterface::class),
            $logger,
        );

        $this->expectException(RuntimeException::class);

        $fetcher->sync(true);
    }

    #[Test]
    public function admins_are_notified_on_the_next_sync_when_notifying_them_failed(): void
    {
        $this->settings->set(AbandonedExtensionsFetcher::NOTIFY_ADMINS_SETTING, '1');
        $fetcher = $this->fetcher();

        $fetcher->failNextNotification = true;

        try {
            $fetcher->sync(true);
            $this->fail('The failed notification should be rethrown.');
        } catch (RuntimeException) {
        }

        $fetcher->sync(true);

        $this->assertEquals([['vendor/old-a'], ['vendor/old-a']], $fetcher->notified);
    }

    #[Test]
    public function a_failed_notification_is_logged(): void
    {
        $this->settings->set(AbandonedExtensionsFetcher::NOTIFY_ADMINS_SETTING, '1');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with($this->stringContains('mail server unreachable'));

        $fetcher = $this->fetcher($logger, 1);
        $fetcher->failNextNotification = true;

        $this->expectException(RuntimeException::class);

        $fetcher->sync(true);
    }

    #[Test]
    public function admins_are_notified_about_an_abandoned_extension_once(): void
    {
        $this->settings->set(AbandonedExtensionsFetcher::NOTIFY_ADMINS_SETTING, '1');
        $fetcher = $this->fetcher();

        $fetcher->sync(true);
        $fetcher->sync(true);

        $this->assertEquals([['vendor/old-a']], $fetcher->notified);
    }

    #[Test]
    public function extensions_flagged_while_notifications_were_off_are_not_notified_later(): void
    {
        $fetcher = $this->fetcher();

        $fetcher->sync(true);

        $this->settings->set(AbandonedExtensionsFetcher::NOTIFY_ADMINS_SETTING, '1');

        $fetcher->sync(true);

        $this->assertEquals([], $fetcher->notified);
    }
}

class RecordingAbandonedExtensionsFetcher extends AbandonedExtensionsFetcher
{
    /** @var string[][] */
    public array $notified = [];

    public bool $failNextNotification = false;

    protected function notifyAdmins(array $newPackages, array $map): void
    {
        $this->notified[] = $newPackages;

        if ($this->failNextNotification) {
            $this->failNextNotification = false;

            throw new RuntimeException('mail server unreachable');
        }
    }
}
