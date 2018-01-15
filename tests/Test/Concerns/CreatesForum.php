<?php

namespace Flarum\Tests\Test\Concerns;

use Flarum\Foundation\Application;
use Flarum\Foundation\Site;
use Flarum\Http\Server;
use Flarum\Install\Console\DataProviderInterface;
use Flarum\Install\Console\DefaultsDataProvider;
use Flarum\Install\Console\InstallCommand;
use Flarum\Install\InstallServiceProvider;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;

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

    protected function createsHttpForum()
    {
        $this->http = Server::fromSite(
            $this->site = new Site
        );
    }

    protected function refreshApplication()
    {
        $this->createsHttpForum();

        $data = new DefaultsDataProvider();

        $database = $data->getDatabaseConfiguration();

        $database['username'] = 'travis';
        $database['password'] = '';

        $data->setDatabaseConfiguration($database);

        $this->site->setConfig([
            'url' => $data->getBaseUrl(),
            'debug' => true,
            'database' => $data->getDatabaseConfiguration()
        ]);

        $this->app = $this->http->app;

        $this->installsForum($data);
    }

    protected function installsForum(DataProviderInterface $data)
    {
        $this->app->register(InstallServiceProvider::class);
        /** @var InstallCommand $command */
        $command = $this->app->make(InstallCommand::class);
        $command->setDataSource($data);

        $body = fopen('php://temp', 'wb+');
        $input = new StringInput('');
        $output = new StreamOutput($body);

        $command->run($input, $output);
    }
}
