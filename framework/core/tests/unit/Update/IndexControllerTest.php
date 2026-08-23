<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Update;

use Flarum\Foundation\Config;
use Flarum\Testing\unit\TestCase;
use Flarum\Update\Controller\IndexController;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Laminas\Diactoros\ServerRequest;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;

/**
 * The updater form asks for whatever config.php can prove ownership with, and
 * that differs by driver: a username and password for MySQL/MariaDB/PostgreSQL,
 * the database file's name for SQLite, which has neither.
 *
 * The form must ask for exactly what the controller then checks — no more, no
 * less — so this pins the flag the view branches on to the same condition the
 * verification uses.
 */
class IndexControllerTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    private function usesDatabaseNameFor(array $database): bool
    {
        $captured = false;

        // The update view records the value it is handed for `usesDatabaseName`.
        $updateView = m::mock(View::class);
        $updateView->shouldReceive('with')
            ->with('usesDatabaseName', m::capture($captured))
            ->andReturnSelf();

        // The outer app view is passed through untouched.
        $appView = m::mock(View::class);
        $appView->shouldReceive('with')->andReturnSelf();
        $appView->shouldReceive('render')->andReturn('');

        $factory = m::mock(Factory::class);
        $factory->shouldReceive('make')->with('flarum.update::app')->andReturn($appView);
        $factory->shouldReceive('make')->with('flarum.update::update')->andReturn($updateView);

        $config = new Config(['url' => 'http://flarum.test', 'database' => $database]);

        (new IndexController($factory, $config))->render(new ServerRequest());

        return $captured;
    }

    #[Test]
    public function sqlite_asks_for_the_database_name()
    {
        $this->assertTrue($this->usesDatabaseNameFor([
            'driver' => 'sqlite',
            'database' => 'flarum.sqlite',
            'username' => null,
            'password' => null,
        ]));
    }

    #[Test]
    public function a_credentialed_database_asks_for_username_and_password()
    {
        $this->assertFalse($this->usesDatabaseNameFor([
            'driver' => 'mysql',
            'database' => 'flarum',
            'username' => 'flarum',
            'password' => 's3cret',
        ]));
    }

    #[Test]
    public function a_passwordless_credentialed_database_still_asks_for_the_username()
    {
        // MySQL/MariaDB over a socket has a username but no password. It is not
        // SQLite, so it must still use the credential form, not the
        // database-name one.
        $this->assertFalse($this->usesDatabaseNameFor([
            'driver' => 'mysql',
            'database' => 'flarum',
            'username' => 'flarum',
            'password' => null,
        ]));
    }
}
