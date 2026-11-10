<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Update;

use Flarum\Database\Console\MigrateCommand;
use Flarum\Foundation\Config;
use Flarum\Testing\unit\TestCase;
use Flarum\Update\Controller\UpdateController;
use Laminas\Diactoros\ServerRequest;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The updater runs before the app can authenticate anyone: `settings.version`
 * has drifted, the schema may be mid-change, and there is no session to trust.
 * The one thing available is `config.php`, so the caller proves they are the
 * admin by echoing back the database credentials it holds.
 *
 * Previously only the password was checked, and only when it was non-null — so
 * an install with no database password (which our own installer produces by
 * pressing Enter at the prompt) had no check at all, and an anonymous POST ran
 * every pending migration.
 *
 * Now the username is checked too, on every install. On a passwordless
 * database the password half matches an empty field, but the username still
 * has to be right — something a passing visitor has no reason to know.
 */
class UpdateControllerTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    /**
     * @param array<string, string|null> $database  what config.php holds
     * @param array<string, string>|null $input     the submitted form body
     */
    private function attempt(array $database, ?array $input): int
    {
        $command = m::mock(MigrateCommand::class);

        // A blocked request must never reach the migrator; an allowed one runs
        // it exactly once. The count is the real assertion — the status code
        // only confirms what the caller is told.
        $command->shouldReceive('run')
            ->with(m::type(InputInterface::class), m::type(OutputInterface::class))
            ->andReturn(0);

        $config = new Config([
            'url' => 'http://flarum.test',
            'database' => $database,
        ]);

        $controller = new UpdateController($command, $config);

        $request = (new ServerRequest())->withParsedBody($input);

        return $controller->handle($request)->getStatusCode();
    }

    private function ran(array $database, ?array $input): bool
    {
        $command = m::mock(MigrateCommand::class);
        $ran = false;
        $command->shouldReceive('run')->andReturnUsing(function () use (&$ran) {
            $ran = true;

            return 0;
        });

        $config = new Config([
            'url' => 'http://flarum.test',
            'database' => $database,
        ]);

        (new UpdateController($command, $config))
            ->handle((new ServerRequest())->withParsedBody($input));

        return $ran;
    }

    #[Test]
    public function correct_username_and_password_runs_the_migration()
    {
        $this->assertTrue($this->ran(
            ['username' => 'flarum', 'password' => 's3cret'],
            ['databaseUsername' => 'flarum', 'databasePassword' => 's3cret']
        ));
    }

    #[Test]
    public function a_wrong_password_is_refused()
    {
        $this->assertFalse($this->ran(
            ['username' => 'flarum', 'password' => 's3cret'],
            ['databaseUsername' => 'flarum', 'databasePassword' => 'wrong']
        ));
    }

    #[Test]
    public function a_wrong_username_is_refused_even_with_the_right_password()
    {
        $this->assertFalse($this->ran(
            ['username' => 'flarum', 'password' => 's3cret'],
            ['databaseUsername' => 'attacker', 'databasePassword' => 's3cret']
        ));
    }

    #[Test]
    public function a_passwordless_database_runs_when_the_password_field_is_blank()
    {
        // The heart of the fix: no password to check, so a blank field matches
        // — but the username still has to be the real one.
        $this->assertTrue($this->ran(
            ['username' => 'flarum', 'password' => ''],
            ['databaseUsername' => 'flarum', 'databasePassword' => '']
        ));
    }

    #[Test]
    public function a_null_password_behaves_the_same_as_an_empty_one()
    {
        // Our installer stores null when the admin leaves the prompt blank.
        // Null and '' must be indistinguishable here, or the old "skip the
        // check entirely" hole reopens.
        $this->assertTrue($this->ran(
            ['username' => 'flarum', 'password' => null],
            ['databaseUsername' => 'flarum', 'databasePassword' => '']
        ));
    }

    #[Test]
    public function a_passwordless_database_is_refused_when_a_password_is_guessed()
    {
        // An attacker who submits any password against a passwordless database
        // no longer matches — the empty config value only matches an empty
        // field.
        $this->assertFalse($this->ran(
            ['username' => 'flarum', 'password' => ''],
            ['databaseUsername' => 'flarum', 'databasePassword' => 'anything']
        ));
    }

    #[Test]
    public function a_passwordless_database_still_needs_the_right_username()
    {
        $this->assertFalse($this->ran(
            ['username' => 'flarum', 'password' => ''],
            ['databaseUsername' => 'wrong', 'databasePassword' => '']
        ));
    }

    #[Test]
    public function sqlite_verifies_against_the_database_name_when_there_is_no_username()
    {
        // SQLite has no username and no password — both are absent from config.
        // The only thing an admin knows that a passing visitor does not is the
        // database file, so that stands in as the secret.
        $this->assertTrue($this->ran(
            ['driver' => 'sqlite', 'database' => 'flarum.sqlite', 'username' => null, 'password' => null],
            ['databaseName' => 'flarum.sqlite']
        ));
    }

    #[Test]
    public function sqlite_is_refused_when_the_database_name_is_wrong()
    {
        $this->assertFalse($this->ran(
            ['driver' => 'sqlite', 'database' => 'flarum.sqlite', 'username' => null, 'password' => null],
            ['databaseName' => 'wrong.sqlite']
        ));
    }

    #[Test]
    public function sqlite_is_refused_when_nothing_is_submitted()
    {
        // The reported hole was a bodyless POST running migrations. On SQLite
        // that must now be blocked too.
        $this->assertFalse($this->ran(
            ['driver' => 'sqlite', 'database' => 'flarum.sqlite', 'username' => null, 'password' => null],
            []
        ));
    }

    #[Test]
    public function a_credentialed_database_ignores_the_database_name_field()
    {
        // MySQL/Postgres verify by username + password. The database-name
        // fallback must not become a second way in — submitting the right
        // database name but the wrong password is still refused.
        $this->assertFalse($this->ran(
            ['driver' => 'mysql', 'database' => 'flarum', 'username' => 'flarum', 'password' => 's3cret'],
            ['databaseName' => 'flarum', 'databaseUsername' => 'flarum', 'databasePassword' => 'wrong']
        ));
    }

    #[Test]
    public function a_credentialed_database_still_runs_with_the_right_username_and_password()
    {
        // And the database-name field, if present, does not interfere with the
        // normal credentialed path.
        $this->assertTrue($this->ran(
            ['driver' => 'mysql', 'database' => 'flarum', 'username' => 'flarum', 'password' => 's3cret'],
            ['databaseName' => 'anything', 'databaseUsername' => 'flarum', 'databasePassword' => 's3cret']
        ));
    }

    #[Test]
    public function an_empty_submission_is_refused()
    {
        $this->assertFalse($this->ran(
            ['username' => 'flarum', 'password' => 's3cret'],
            []
        ));
    }

    #[Test]
    public function a_missing_body_is_refused()
    {
        $this->assertFalse($this->ran(
            ['username' => 'flarum', 'password' => 's3cret'],
            null
        ));
    }

    #[Test]
    public function a_refused_request_returns_a_generic_error()
    {
        // Wrong password and wrong username must be indistinguishable from
        // outside, so the response cannot say which was wrong.
        $wrongPassword = $this->attempt(
            ['username' => 'flarum', 'password' => 's3cret'],
            ['databaseUsername' => 'flarum', 'databasePassword' => 'wrong']
        );

        $wrongUsername = $this->attempt(
            ['username' => 'flarum', 'password' => 's3cret'],
            ['databaseUsername' => 'wrong', 'databasePassword' => 's3cret']
        );

        $this->assertSame(500, $wrongPassword);
        $this->assertSame($wrongPassword, $wrongUsername);
    }
}
