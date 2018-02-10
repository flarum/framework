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
use Flarum\Bus\BusServiceProvider as BusProvider;
use Flarum\Database\DatabaseServiceProvider;
use Flarum\Database\MigrationServiceProvider;
use Flarum\Discussion\DiscussionServiceProvider;
use Flarum\Extension\ExtensionServiceProvider;
use Flarum\Formatter\FormatterServiceProvider;
use Flarum\Forum\ForumServiceProvider;
use Flarum\Group\GroupServiceProvider;
use Flarum\Locale\LocaleServiceProvider;
use Flarum\Notification\NotificationServiceProvider;
use Flarum\Post\PostServiceProvider;
use Flarum\Search\SearchServiceProvider;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Settings\SettingsServiceProvider;
use Flarum\User\UserServiceProvider;
use Illuminate\Bus\BusServiceProvider;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Hashing\HashServiceProvider;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Session\SessionServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\ViewServiceProvider;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

// TODO: This should be an interface maybe?
class Site
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $publicPath;

    /**
     * @var string
     */
    protected $storagePath;

    /**
     * @var array
     */
    protected $config;

    protected $extenders = [];

    public function __construct()
    {
        $this->basePath = getcwd();
        $this->publicPath = $this->basePath;
    }

    /**
     * @return Application
     */
    public function boot()
    {
        return $this->getApp();
    }

    /**
     * @param $basePath
     * @return static
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * @param $publicPath
     * @return static
     */
    public function setPublicPath($publicPath)
    {
        $this->publicPath = $publicPath;

        return $this;
    }

    /**
     * @param $storagePath
     * @return static
     */
    public function setStoragePath($storagePath)
    {
        $this->storagePath = $storagePath;

        return $this;
    }

    /**
     * @param array $config
     * @return static
     */
    public function setConfig(array $config)
    {
        $this->config = $config;

        return $this;
    }

    protected function getConfig()
    {
        if (empty($this->config) && file_exists($file = $this->basePath.'/config.php')) {
            $this->config = include $file;
        }

        return $this->config;
    }

    /**
     * @return Application
     */
    protected function getApp()
    {
        if ($this->app !== null) {
            return $this->app;
        }

        date_default_timezone_set('UTC');

        $app = new Application($this->basePath, $this->publicPath);

        if ($this->storagePath) {
            $app->useStoragePath($this->storagePath);
        }

        $app->instance('env', 'production');
        $app->instance('flarum.config', $this->getConfig());
        $app->instance('config', $config = $this->getIlluminateConfig($app));

        $this->registerLogger($app);

        $this->registerCache($app);

        $app->register(DatabaseServiceProvider::class);
        $app->register(MigrationServiceProvider::class);
        $app->register(SettingsServiceProvider::class);
        $app->register(LocaleServiceProvider::class);
        $app->register(BusServiceProvider::class);
        $app->register(FilesystemServiceProvider::class);
        $app->register(HashServiceProvider::class);
        $app->register(MailServiceProvider::class);
        $app->register(SessionServiceProvider::class);
        $app->register(ViewServiceProvider::class);
        $app->register(ValidationServiceProvider::class);

        $app->register(BusProvider::class);

        if ($app->isInstalled() && $app->isUpToDate()) {
            $settings = $app->make(SettingsRepositoryInterface::class);

            $config->set('mail.driver', $settings->get('mail_driver'));
            $config->set('mail.host', $settings->get('mail_host'));
            $config->set('mail.port', $settings->get('mail_port'));
            $config->set('mail.from.address', $settings->get('mail_from'));
            $config->set('mail.from.name', $settings->get('forum_title'));
            $config->set('mail.encryption', $settings->get('mail_encryption'));
            $config->set('mail.username', $settings->get('mail_username'));
            $config->set('mail.password', $settings->get('mail_password'));

            $app->register(DiscussionServiceProvider::class);
            $app->register(FormatterServiceProvider::class);
            $app->register(GroupServiceProvider::class);
            $app->register(NotificationServiceProvider::class);
            $app->register(PostServiceProvider::class);
            $app->register(SearchServiceProvider::class);
            $app->register(UserServiceProvider::class);

            $app->register(ApiServiceProvider::class);
            $app->register(ForumServiceProvider::class);
            $app->register(AdminServiceProvider::class);

            foreach ($this->extenders as $extender) {
                // TODO: Create extenders architecture
                // $extender->apply($app);
            }

            $app->register(ExtensionServiceProvider::class);
        }

        $app->boot();

        $this->app = $app;

        return $app;
    }

    /**
     * @param Application $app
     * @return ConfigRepository
     */
    protected function getIlluminateConfig(Application $app)
    {
        return new ConfigRepository([
            'view' => [
                'paths' => [],
                'compiled' => $app->storagePath().'/views',
            ],
            'mail' => [
                'driver' => 'mail',
            ],
            'filesystems' => [
                'default' => 'local',
                'cloud' => 's3',
                'disks' => [
                    'flarum-avatars' => [
                        'driver' => 'local',
                        'root'   => $app->publicPath().'/assets/avatars'
                    ]
                ]
            ],
            'session' => [
                'driver' => 'file',
                'lifetime' => 120,
                'expire_on_close' => false,
                'encrypt' => false,
                'files' => $app->storagePath().'/sessions',
                'lottery' => [2, 100],
                'cookie' => 'session'
            ]
        ]);
    }

    /**
     * @param Application $app
     */
    protected function registerLogger(Application $app)
    {
        $logger = new Logger($app->environment());
        $logPath = $app->storagePath().'/logs/flarum.log';

        $handler = new StreamHandler($logPath, Logger::DEBUG);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        $logger->pushHandler($handler);

        $app->instance('log', $logger);
        $app->alias('log', 'Psr\Log\LoggerInterface');
    }

    /**
     * @param Application $app
     */
    protected function registerCache(Application $app)
    {
        $app->singleton('cache.store', function ($app) {
            return new \Illuminate\Cache\Repository($app->make('cache.filestore'));
        });

        $app->singleton('cache.filestore', function ($app) {
            return new \Illuminate\Cache\FileStore(
                new \Illuminate\Filesystem\Filesystem(),
                $app->storagePath().'/cache'
            );
        });

        $app->alias('cache.filestore', 'Illuminate\Contracts\Cache\Store');
        $app->alias('cache.store', 'Illuminate\Contracts\Cache\Repository');
    }
}
