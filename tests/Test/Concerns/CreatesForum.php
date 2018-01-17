<?php

namespace Flarum\Tests\Test\Concerns;

use Flarum\Foundation\Application;
use Flarum\Foundation\Site;
use Flarum\Http\Server;
use Flarum\Install\Console\DataProviderInterface;
use Flarum\Install\Console\DefaultsDataProvider;

trait CreatesForum
{
    /**
     * @var Server
     */
    protected $http;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var DataProviderInterface
     */
    protected $configuration;

    protected function createsSite()
    {
        $this->site = (new Site)
            ->setBasePath(__DIR__ . '/../../tmp')
            ->setPublicPath(__DIR__ . '/../../tmp/public');
    }

    protected function createsHttpForum()
    {
        $this->http = Server::fromSite(
            $this->site
        );
        $this->app = $this->http->app;
    }

    protected function refreshApplication()
    {
        $this->createsSite();

        $data = new DefaultsDataProvider();

        $data->setDebug();

        $database = $data->getDatabaseConfiguration();

        $database['database'] = env('DB_DATABASE', 'flarum');
        $database['username'] = env('DB_USERNAME', 'root');
        $database['password'] = env('DB_PASSWORD', '');

        $data->setDatabaseConfiguration($database);
        $this->configuration = $data;

        $this->createsHttpForum();
    }
}
