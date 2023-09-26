<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Admin\AdminServiceProvider;
use Flarum\Api\ApiServiceProvider;
use Flarum\Bus\BusServiceProvider;
use Flarum\Console\ConsoleServiceProvider;
use Flarum\Database\DatabaseServiceProvider;
use Flarum\Discussion\DiscussionServiceProvider;
use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\ExtensionServiceProvider;
use Flarum\Filesystem\FilesystemServiceProvider;
use Flarum\Formatter\FormatterServiceProvider;
use Flarum\Forum\ForumServiceProvider;
use Flarum\Frontend\FrontendServiceProvider;
use Flarum\Group\GroupServiceProvider;
use Flarum\Http\HttpServiceProvider;
use Flarum\Locale\LocaleServiceProvider;
use Flarum\Mail\MailServiceProvider;
use Flarum\Notification\NotificationServiceProvider;
use Flarum\Post\PostServiceProvider;
use Flarum\Queue\QueueServiceProvider;
use Flarum\Search\SearchServiceProvider;
use Flarum\Settings\SettingsServiceProvider;
use Flarum\Update\UpdateServiceProvider;
use Flarum\User\SessionServiceProvider;
use Flarum\User\UserServiceProvider;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Hashing\HashServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\ViewServiceProvider;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class InstalledSite implements SiteInterface
{
    /**
     * @var ExtenderInterface[]
     */
    protected array $extenders = [];

    public function __construct(
        protected Paths $paths,
        protected Config $config
    ) {
    }

    /**
     * Create and boot a Flarum application instance.
     *
     * @return InstalledApp
     */
    public function bootApp(): AppInterface
    {
        return new InstalledApp(
            $this->bootLaravel(),
            $this->config
        );
    }

    /**
     * @param ExtenderInterface[] $extenders
     * @return InstalledSite
     */
    public function extendWith(array $extenders): self
    {
        $this->extenders = $extenders;

        return $this;
    }

    protected function bootLaravel(): Container
    {
        $app = new Application($this->paths);

        $app->instance('env', $this->config->environment());
        $app->instance('flarum.config', $this->config);
        $app->alias('flarum.config', Config::class);
        $app->instance('flarum.debug', $this->config->inDebugMode());
        $app->instance('config', $this->getIlluminateConfig());
        $app->instance('flarum.maintenance.handler', new MaintenanceModeHandler);

        $this->registerLogger($app);
        $this->registerCache($app);

        $app->register(AdminServiceProvider::class);
        $app->register(ApiServiceProvider::class);
        $app->register(BusServiceProvider::class);
        $app->register(ConsoleServiceProvider::class);
        $app->register(DatabaseServiceProvider::class);
        $app->register(DiscussionServiceProvider::class);
        $app->register(ExtensionServiceProvider::class);
        $app->register(ErrorServiceProvider::class);
        $app->register(FilesystemServiceProvider::class);
        $app->register(FormatterServiceProvider::class);
        $app->register(ForumServiceProvider::class);
        $app->register(FrontendServiceProvider::class);
        $app->register(GroupServiceProvider::class);
        $app->register(HashServiceProvider::class);
        $app->register(HttpServiceProvider::class);
        $app->register(LocaleServiceProvider::class);
        $app->register(MailServiceProvider::class);
        $app->register(NotificationServiceProvider::class);
        $app->register(PostServiceProvider::class);
        $app->register(QueueServiceProvider::class);
        $app->register(SearchServiceProvider::class);
        $app->register(SessionServiceProvider::class);
        $app->register(SettingsServiceProvider::class);
        $app->register(UpdateServiceProvider::class);
        $app->register(UserServiceProvider::class);
        $app->register(ValidationServiceProvider::class);
        $app->register(ViewServiceProvider::class);

        $app->booting(function () use ($app) {
            // Run all local-site extenders before booting service providers
            // (but after those from "real" extensions, which have been set up
            // in a service provider above).
            foreach ($this->extenders as $extension) {
                $extension->extend($app);
            }
        });

        $app->boot();

        return $app;
    }

    protected function getIlluminateConfig(): ConfigRepository
    {
        return new ConfigRepository([
            'app' => [
                'timezone' => 'UTC'
            ],
            'view' => [
                'paths' => [],
                'compiled' => $this->paths->storage.'/views',
            ],
            'session' => [
                'lifetime' => 120,
                'files' => $this->paths->storage.'/sessions',
                'cookie' => 'session'
            ]
        ]);
    }

    protected function registerLogger(Container $container): void
    {
        $logPath = $this->paths->storage.'/logs/flarum.log';
        $logLevel = $this->config->inDebugMode() ? Level::Debug : Level::Info;
        $handler = new RotatingFileHandler($logPath, 0, $logLevel);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        $container->instance('log', new Logger('flarum', [$handler]));
        $container->alias('log', LoggerInterface::class);
    }

    protected function registerCache(Container $container): void
    {
        $container->singleton('cache.store', function ($container) {
            return new CacheRepository($container->make('cache.filestore'));
        });
        $container->alias('cache.store', Repository::class);

        $container->singleton('cache.filestore', function () {
            return new FileStore(new Filesystem, $this->paths->storage.'/cache');
        });
        $container->alias('cache.filestore', Store::class);
    }
}
