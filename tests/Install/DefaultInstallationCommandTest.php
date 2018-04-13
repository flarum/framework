<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\Install;

use Flarum\Install\Console\InstallCommand;
use Flarum\Install\InstallServiceProvider;
use Flarum\Tests\Test\TestCase;
use Flarum\User\User;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;

class DefaultInstallationCommandTest extends TestCase
{
    protected $isInstalled = false;

    /**
     * @test
     */
    public function allows_forum_installation()
    {
        if (file_exists($this->app->basePath().DIRECTORY_SEPARATOR.'config.php')) {
            unlink($this->app->basePath().DIRECTORY_SEPARATOR.'config.php');
        }

        $this->app->register(InstallServiceProvider::class);
        /** @var InstallCommand $command */
        $command = $this->app->make(InstallCommand::class);
        $command->setDataSource($this->configuration);

        $body = fopen('php://temp', 'wb+');
        $input = new StringInput('');
        $output = new StreamOutput($body);

        $command->run($input, $output);

        $this->assertFileExists($this->app->basePath().DIRECTORY_SEPARATOR.'config.php');

        $admin = $this->configuration->getAdminUser();

        $this->assertEquals(User::find(1)->username, $admin['username']);
    }
}
