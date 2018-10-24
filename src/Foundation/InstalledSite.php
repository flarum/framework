<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Admin\AdminServiceProvider;
use Flarum\Api\ApiServiceProvider;
use Flarum\Bus\BusServiceProvider;
use Flarum\Database\DatabaseServiceProvider;
use Flarum\Database\MigrationServiceProvider;
use Flarum\Discussion\DiscussionServiceProvider;
use Flarum\Extension\ExtensionServiceProvider;
use Flarum\Formatter\FormatterServiceProvider;
use Flarum\Forum\ForumServiceProvider;
use Flarum\Frontend\FrontendServiceProvider;
use Flarum\Group\GroupServiceProvider;
use Flarum\Locale\LocaleServiceProvider;
use Flarum\Notification\NotificationServiceProvider;
use Flarum\Post\PostServiceProvider;
use Flarum\Search\SearchServiceProvider;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Settings\SettingsServiceProvider;
use Flarum\Update\UpdateServiceProvider;
use Flarum\User\SessionServiceProvider;
use Flarum\User\UserServiceProvider;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Hashing\HashServiceProvider;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\ViewServiceProvider;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class InstalledSite implements SiteInterface
{
    /**
     * @var array
     */
    private $paths;

    /**
     * @var array
     */
    private $config;

    /**
     * @var \Flarum\Extend\ExtenderInterface[]
     */
    private $extenders = [];

    public function __construct(array $paths, array $config)
    {
        $this->paths = $paths;
        $this->config = $config;
    }

    /**
     * Create and boot a Flarum application instance.
     *
     * @return AppInterface
     */
    public function bootApp(): AppInterface
    {
        return new InstalledApp(
            $this->bootLaravel(),
            $this->config
        );
    }

    /**
     * @param \Flarum\Extend\ExtenderInterface[] $extenders
     * @return InstalledSite
     */
    public function extendWith(array $extenders): self
    {
        $this->extenders = $extenders;

        return $this;
    }

    private function bootLaravel(): Application
    {
        $laravel = new Application($this->paths['base'], $this->paths['public']);

        $laravel->useStoragePath($this->paths['storage']);

        $laravel->instance('env', 'production');
        $laravel->instance('flarum.config', $this->config);
        $laravel->instance('config', $config = $this->getIlluminateConfig($laravel));

        $this->registerLogger($laravel);
        $this->registerCache($laravel);

        $laravel->register(DatabaseServiceProvider::class);
        $laravel->register(MigrationServiceProvider::class);
        $laravel->register(SettingsServiceProvider::class);
        $laravel->register(LocaleServiceProvider::class);
        $laravel->register(BusServiceProvider::class);
        $laravel->register(FilesystemServiceProvider::class);
        $laravel->register(HashServiceProvider::class);
        $laravel->register(MailServiceProvider::class);
        $laravel->register(ViewServiceProvider::class);
        $laravel->register(ValidationServiceProvider::class);

        $settings = $laravel->make(SettingsRepositoryInterface::class);

        $config->set('mail.driver', $settings->get('mail_driver'));
        $config->set('mail.host', $settings->get('mail_host'));
        $config->set('mail.port', $settings->get('mail_port'));
        $config->set('mail.from.address', $settings->get('mail_from'));
        $config->set('mail.from.name', $settings->get('forum_title'));
        $config->set('mail.encryption', $settings->get('mail_encryption'));
        $config->set('mail.username', $settings->get('mail_username'));
        $config->set('mail.password', $settings->get('mail_password'));

        $laravel->register(DiscussionServiceProvider::class);
        $laravel->register(FormatterServiceProvider::class);
        $laravel->register(FrontendServiceProvider::class);
        $laravel->register(GroupServiceProvider::class);
        $laravel->register(NotificationServiceProvider::class);
        $laravel->register(PostServiceProvider::class);
        $laravel->register(SearchServiceProvider::class);
        $laravel->register(SessionServiceProvider::class);
        $laravel->register(UserServiceProvider::class);
        $laravel->register(UpdateServiceProvider::class);

        $laravel->register(ApiServiceProvider::class);
        $laravel->register(ForumServiceProvider::class);
        $laravel->register(AdminServiceProvider::class);

        $laravel->register(ExtensionServiceProvider::class);

        $laravel->boot();

        foreach ($this->extenders as $extension) {
            $extension->extend($laravel);
        }

        return $laravel;
    }

    /**
     * @param Application $app
     * @return ConfigRepository
     */
    private function getIlluminateConfig(Application $app)
    {
        return new ConfigRepository([
            'view' => [
                'paths' => [],
                'compiled' => $this->paths['storage'].'/views',
            ],
            'mail' => [
                'driver' => 'mail',
            ],
            'filesystems' => [
                'default' => 'local',
                'cloud' => 's3',
                'disks' => [
                    'flarum-assets' => [
                        'driver' => 'local',
                        'root'   => $this->paths['public'].'/assets',
                        'url'    => $app->url('assets')
                    ],
                    'flarum-avatars' => [
                        'driver' => 'local',
                        'root'   => $this->paths['public'].'/assets/avatars'
                    ]
                ]
            ],
            'session' => [
                'lifetime' => 120,
                'files' => $this->paths['storage'].'/sessions',
                'cookie' => 'session'
            ]
        ]);
    }

    private function registerLogger(Application $app)
    {
        $logPath = $this->paths['storage'].'/logs/flarum.log';
        $handler = new RotatingFileHandler($logPath, Logger::INFO);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        $app->instance('log', new Logger($app->environment(), [$handler]));
        $app->alias('log', LoggerInterface::class);
    }

    private function registerCache(Application $app)
    {
        $app->singleton('cache.store', function ($app) {
            return new CacheRepository($app->make('cache.filestore'));
        });
        $app->alias('cache.store', Repository::class);

        $app->singleton('cache.filestore', function () {
            return new FileStore(new Filesystem, $this->paths['storage'].'/cache');
        });
        $app->alias('cache.filestore', Store::class);
    }
}
