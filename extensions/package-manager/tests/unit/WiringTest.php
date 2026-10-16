<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\ExtensionManagerServiceProvider;
use Flarum\ExtensionManager\Run\Drivers;
use Flarum\ExtensionManager\Run\RunStore;
use Flarum\ExtensionManager\Run\StepRunner;
use Flarum\ExtensionManager\Run\StepsFactory;
use Flarum\Foundation\Config;
use Flarum\Foundation\Paths;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * The application actually assembles the things the other tests exercise.
 *
 * 🚨 THIS IS THE TEST THAT WAS MISSING, and its absence is the most expensive
 * mistake in this codebase so far.
 *
 * Every other test here builds its subject by hand, which means a unit can be
 * perfectly correct, perfectly tested, and never reached. That is exactly what
 * happened: `AutoRollbackTest` proved `Verdict::from()` decides correctly, and
 * passed on every run, while the automatic rollback was disconnected on every
 * install in the world — because the binding that builds the steps did not pass
 * the Config they need to know the site's address.
 *
 * A test that constructs its own subject cannot see that. This one resolves
 * from the container the way the application does, and asserts the graph is
 * whole.
 */
class WiringTest extends TestCase
{
    private string $dir;
    private Container $container;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-wiring-'.bin2hex(random_bytes(6));

        foreach (['', '/public', '/storage', '/vendor'] as $sub) {
            mkdir($this->dir.$sub, 0775, true);
        }

        $this->container = new Container();

        $this->container->instance(Paths::class, new Paths([
            'base' => $this->dir,
            'public' => $this->dir.'/public',
            'storage' => $this->dir.'/storage',
            'vendor' => $this->dir.'/vendor',
        ]));

        $this->container->instance(Config::class, new Config([
            'debug' => false,
            'url' => 'https://example.test',
        ]));

        /*
         * Mocked, not hand-rolled. A stub class that implements an interface by
         * hand stops compiling the day Laravel adds a method to it, which turns
         * a wiring test into a maintenance chore and gets it deleted.
         */
        $queue = $this->createStub(QueueFactory::class);
        $queue->method('connection')->willReturn(new \Illuminate\Queue\SyncQueue());

        $this->container->instance(QueueFactory::class, $queue);
        $this->container->instance(Dispatcher::class, $this->createStub(Dispatcher::class));

        /*
         * Flarum's AbstractServiceProvider takes the Application contract and
         * does nothing with it but assign it, so a mock that forwards the three
         * container methods the provider uses is a faithful stand-in — and does
         * not drag a full framework Application into a unit test.
         */
        $real = $this->container;

        $app = $this->createStub(ApplicationContract::class);
        $app->method('singleton')->willReturnCallback(fn ($a, $c = null) => $real->singleton($a, $c));
        $app->method('bind')->willReturnCallback(fn ($a, $c = null, $shared = false) => $real->bind($a, $c, $shared));
        $app->method('instance')->willReturnCallback(fn ($a, $i) => $real->instance($a, $i));
        $app->method('make')->willReturnCallback(fn ($a, array $p = []) => $real->make($a, $p));

        (new ExtensionManagerServiceProvider($app))->register();
    }

    protected function tearDown(): void
    {
        $this->rmdir($this->dir);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('services')]
    public function test_every_service_resolves(string $service): void
    {
        $this->assertInstanceOf($service, $this->container->make($service));
    }

    public static function services(): array
    {
        return [
            RunStore::class => [RunStore::class],
            Drivers::class => [Drivers::class],
            StepRunner::class => [StepRunner::class],
        ];
    }

    public function test_the_steps_factory_resolves(): void
    {
        $this->assertInstanceOf(StepsFactory::class, $this->container->make(StepsFactory::class));
    }

    /**
     * 🚨 The safety net is REACHABLE, not merely correct.
     *
     * The site address is what turns the health check on. With it empty, every
     * run reports "this update will not be judged by whether the site answers"
     * and the rollback tested in AutoRollbackTest can never fire. Asserting the
     * rule without asserting this is how it stayed broken.
     */
    public function test_the_health_check_knows_the_site_address(): void
    {
        $factory = $this->container->make(StepsFactory::class);

        $method = (new ReflectionClass($factory))->getMethod('siteUrl');

        $this->assertSame(
            'https://example.test/',
            $method->invoke($factory),
            'The steps cannot reach the site, so the health check and the automatic rollback are off.'
        );
    }

    /**
     * And the other direction.
     *
     * 🚨 Flarum's own Config REFUSES to be built without a `url` key, so "no
     * address configured" is not actually reachable through it — which is worth
     * knowing, because it means an empty site URL in production could only ever
     * have come from the address never being ASKED for. Which is exactly what
     * happened.
     *
     * What remains reachable is an address that is not something to fetch. A
     * checker pointed at a non-HTTP URL would fail every time and undo every
     * update, so this must degrade to "not judged" rather than to a checker.
     */
    public function test_an_address_that_is_not_http_disables_the_check_rather_than_guessing(): void
    {
        $this->container->instance(Config::class, new Config([
            'debug' => false,
            'url' => 'not-a-url',
        ]));

        $factory = $this->container->make(StepsFactory::class);

        $method = (new ReflectionClass($factory))->getMethod('siteUrl');

        $this->assertSame(
            '',
            $method->invoke($factory),
            'A checker pointed at something it cannot fetch would fail every run and undo every update.'
        );
    }

    private function rmdir(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $full = $path.'/'.$entry;
            is_dir($full) ? $this->rmdir($full) : @unlink($full);
        }

        @rmdir($path);
    }
}
