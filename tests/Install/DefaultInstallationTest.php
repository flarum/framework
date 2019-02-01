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

use Flarum\Install\AdminUser;
use Flarum\Install\Installation;
use Flarum\Tests\Test\TestCase;
use Illuminate\Database\Connectors\ConnectionFactory;

class DefaultInstallationTest extends TestCase
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

        /** @var Installation $installation */
        $installation = app(Installation::class);

        $installation
            ->debugMode(true)
            ->baseUrl('http://flarum.local')
            ->databaseConfig($this->getDatabaseConfiguration())
            ->adminUser($this->getAdmin())
            ->settings($this->getSettings())
            ->build()->run();

        $this->assertFileExists(base_path('config.php'));

        $this->assertEquals(
            $this->getDatabase()->table('users')->find(1)->username,
            'admin'
        );
    }

    private function getDatabase()
    {
        $factory = new ConnectionFactory(app());

        return $factory->make($this->getDatabaseConfiguration()->getConfig());
    }

    private function getAdmin(): AdminUser
    {
        return new AdminUser(
            'admin',
            'password',
            'admin@example.com'
        );
    }

    private function getSettings()
    {
        return [
            'forum_title' => 'Development Forum',
            'mail_driver' => 'log',
            'welcome_title' => 'Welcome to Development Forum',
        ];
    }
}
