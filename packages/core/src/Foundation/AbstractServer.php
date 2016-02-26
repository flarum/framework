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

use Illuminate\Config\Repository as ConfigRepository;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

abstract class AbstractServer
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $config;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;

        if (file_exists($file = $this->path.'/config.php')) {
            $this->config = include $file;
        }
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return Application
     */
    protected function getApp()
    {
        date_default_timezone_set('UTC');

        $app = new Application($this->path);

        $app->instance('env', 'production');
        $app->instance('flarum.config', $this->config);
        $app->instance('config', $config = $this->getIlluminateConfig($app));

        $this->registerLogger($app);

        $app->register('Flarum\Database\DatabaseServiceProvider');
        $app->register('Flarum\Settings\SettingsServiceProvider');
        $app->register('Flarum\Locale\LocaleServiceProvider');
        $app->register('Illuminate\Bus\BusServiceProvider');
        $app->register('Illuminate\Cache\CacheServiceProvider');
        $app->register('Illuminate\Filesystem\FilesystemServiceProvider');
        $app->register('Illuminate\Hashing\HashServiceProvider');
        $app->register('Illuminate\Mail\MailServiceProvider');
        $app->register('Illuminate\View\ViewServiceProvider');
        $app->register('Illuminate\Validation\ValidationServiceProvider');

        if ($app->isInstalled() && $app->isUpToDate()) {
            $settings = $app->make('Flarum\Settings\SettingsRepositoryInterface');

            $config->set('mail.driver', $settings->get('mail_driver'));
            $config->set('mail.host', $settings->get('mail_host'));
            $config->set('mail.port', $settings->get('mail_port'));
            $config->set('mail.from.address', $settings->get('mail_from'));
            $config->set('mail.from.name', $settings->get('forum_title'));
            $config->set('mail.encryption', $settings->get('mail_encryption'));
            $config->set('mail.username', $settings->get('mail_username'));
            $config->set('mail.password', $settings->get('mail_password'));

            $app->register('Flarum\Core\CoreServiceProvider');
            $app->register('Flarum\Api\ApiServiceProvider');
            $app->register('Flarum\Forum\ForumServiceProvider');
            $app->register('Flarum\Admin\AdminServiceProvider');
            $app->register('Flarum\Extension\ExtensionServiceProvider');
        }

        $app->boot();

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
            'cache' => [
                'default' => 'file',
                'stores' => [
                    'file' => [
                        'driver' => 'file',
                        'path'   => $app->storagePath().'/cache',
                    ],
                ],
                'prefix' => 'flarum',
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
}
