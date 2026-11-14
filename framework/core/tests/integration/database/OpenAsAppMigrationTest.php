<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\database;

use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class OpenAsAppMigrationTest extends TestCase
{
    private function value(): ?string
    {
        return $this->database()->table('settings')->where('key', 'open_as_app')->value('value');
    }

    private function migrate(): void
    {
        $migration = require __DIR__.'/../../../migrations/2026_10_31_000000_keep_open_as_app_off_for_existing_forums.php';

        $migration['up']($this->database()->getSchemaBuilder());
    }

    #[Test]
    public function a_new_install_opens_as_an_app()
    {
        $this->app();

        $this->assertSame('1', $this->value());
    }

    #[Test]
    public function a_forum_upgrading_from_2_0_keeps_opening_in_the_browser()
    {
        $this->app();
        $this->database()->table('settings')->where('key', 'open_as_app')->delete();

        $this->migrate();

        $this->assertSame('0', $this->value());
    }

    #[Test]
    public function a_value_an_admin_already_saved_is_kept()
    {
        $this->app();

        $this->migrate();

        $this->assertSame('1', $this->value());
    }
}
