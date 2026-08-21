<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Database;

use Flarum\Database\DatabaseRequirements;
use Flarum\Install\DatabaseConfig;
use Flarum\Install\ValidationFailed;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TablePrefixLimitTest extends TestCase
{
    /**
     * The prefix is prepended to generated index names, so what fits depends on how long an
     * identifier the driver accepts.
     */
    #[Test]
    public function max_prefix_length_is_derived_from_the_driver_limit(): void
    {
        $longest = DatabaseRequirements::LONGEST_MIGRATION_IDENTIFIER;

        $this->assertSame(64 - $longest, DatabaseRequirements::maxTablePrefixLength('mysql'));
        $this->assertSame(64 - $longest, DatabaseRequirements::maxTablePrefixLength('mariadb'));
        $this->assertSame(63 - $longest, DatabaseRequirements::maxTablePrefixLength('pgsql'));
    }

    #[Test]
    public function sqlite_has_no_meaningful_prefix_limit(): void
    {
        $this->assertNull(DatabaseRequirements::maxTablePrefixLength('sqlite'));
    }

    /**
     * PostgreSQL allows one character fewer than MySQL, so a prefix can be valid on one and
     * not the other. The installer has to judge it against the driver being installed.
     */
    #[Test]
    public function installer_accepts_a_prefix_that_fits_the_chosen_driver(): void
    {
        $prefix = str_repeat('a', DatabaseRequirements::maxTablePrefixLength('mysql'));

        $config = new DatabaseConfig('mysql', 'localhost', 3306, 'flarum', null, 'flarum', '', $prefix);

        $this->assertSame($prefix, $config->toArray()['prefix']);
    }

    #[Test]
    public function installer_rejects_a_prefix_too_long_for_the_chosen_driver(): void
    {
        $prefix = str_repeat('a', DatabaseRequirements::maxTablePrefixLength('pgsql') + 1);

        $this->expectException(ValidationFailed::class);

        new DatabaseConfig('pgsql', 'localhost', 5432, 'flarum', null, 'flarum', '', $prefix);
    }

    /**
     * The same prefix that PostgreSQL rejects is fine on MySQL, which is the whole reason the
     * limit cannot be a single number.
     */
    #[Test]
    public function a_prefix_can_be_valid_on_one_driver_and_not_another(): void
    {
        $prefix = str_repeat('a', DatabaseRequirements::maxTablePrefixLength('mysql'));

        $this->assertGreaterThan(DatabaseRequirements::maxTablePrefixLength('pgsql'), strlen($prefix));

        new DatabaseConfig('mysql', 'localhost', 3306, 'flarum', null, 'flarum', '', $prefix);

        $this->expectException(ValidationFailed::class);

        new DatabaseConfig('pgsql', 'localhost', 5432, 'flarum', null, 'flarum', '', $prefix);
    }

    /**
     * The prefix is measured in bytes because PostgreSQL counts bytes, and the installer
     * permits Unicode prefixes.
     */
    #[Test]
    public function prefix_length_is_measured_in_bytes(): void
    {
        // Five characters, ten bytes.
        $prefix = str_repeat('é', 5);

        $this->assertSame(10, strlen($prefix));

        $this->expectException(ValidationFailed::class);

        new DatabaseConfig('pgsql', 'localhost', 5432, 'flarum', null, 'flarum', '', $prefix);
    }
}
