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
use Flarum\Tests\Test\TestCase;
use Illuminate\Database\Connectors\ConnectionFactory;
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
        if (file_exists(base_path('config.php'))) {
            unlink(base_path('config.php'));
        }

        /** @var InstallCommand $command */
        $command = app(InstallCommand::class);
        $command->setDataSource($this->configuration);

        $body = fopen('php://temp', 'wb+');
        $input = new StringInput('');
        $output = new StreamOutput($body);

        $command->run($input, $output);

        $this->assertFileExists(base_path('config.php'));

        $admin = $this->configuration->getAdminUser();

        $this->assertEquals(
            $this->getDatabase()->table('users')->find(1)->username,
            $admin['username']
        );
    }

    private function getDatabase()
    {
        $factory = new ConnectionFactory(app());

        return $factory->make($this->configuration->getDatabaseConfiguration());
    }
}
