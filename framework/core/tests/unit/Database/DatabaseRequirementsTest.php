<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Database;

use Flarum\Database\DatabaseRequirements;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class DatabaseRequirementsTest extends TestCase
{
    #[DataProvider('isMariaDbProvider')]
    public function test_detects_mariadb(string $raw, bool $expected): void
    {
        $this->assertSame($expected, DatabaseRequirements::isMariaDb($raw));
    }

    public static function isMariaDbProvider(): array
    {
        return [
            ['10.11.6-MariaDB-1:10.11.6+maria~ubu', true],
            ['5.5.5-10.11.6-MariaDB', true],
            ['8.0.36', false],
            ['8.4.0-0ubuntu0.24.04.1', false],
        ];
    }

    #[DataProvider('normaliseProvider')]
    public function test_normalises_version(string $raw, bool $isMariaDb, ?string $expected): void
    {
        $this->assertSame($expected, DatabaseRequirements::normaliseVersion($raw, $isMariaDb));
    }

    public static function normaliseProvider(): array
    {
        return [
            // Plain MySQL.
            ['8.0.36', false, '8.0.36'],
            ['8.4.0-0ubuntu0.24.04.1', false, '8.4.0'],
            // MariaDB with a distribution suffix.
            ['10.11.14-MariaDB-0+deb12u2', true, '10.11.14'],
            // MariaDB's legacy "5.5.5-" compatibility prefix must be stripped
            // so we read the real version, not 5.5.5.
            ['5.5.5-10.11.6-MariaDB-1:10.11.6+maria~ubu', true, '10.11.6'],
            // PostgreSQL 10+ reports a two-part major.minor version.
            ['16.3', false, '16.3'],
            ['15.13', false, '15.13'],
            // Pre-10 PostgreSQL reported three parts.
            ['9.6.24', false, '9.6.24'],
            // Unparseable.
            ['not-a-version', false, null],
        ];
    }

    #[DataProvider('compareProvider')]
    public function test_compares_against_tiers(string $version, string $minimum, string $recommended, string $expected): void
    {
        $this->assertSame($expected, DatabaseRequirements::compare($version, $minimum, $recommended));
    }

    public static function compareProvider(): array
    {
        return [
            // Below the floor.
            ['5.6.0', '5.7.8', '8.4.0', DatabaseRequirements::BELOW_MINIMUM],
            ['5.7.7', '5.7.8', '8.4.0', DatabaseRequirements::BELOW_MINIMUM],
            // At the floor, below recommended.
            ['5.7.8', '5.7.8', '8.4.0', DatabaseRequirements::BELOW_RECOMMENDED],
            ['8.0.36', '5.7.8', '8.4.0', DatabaseRequirements::BELOW_RECOMMENDED],
            // At or above recommended.
            ['8.4.0', '5.7.8', '8.4.0', DatabaseRequirements::OK],
            ['9.0.0', '5.7.8', '8.4.0', DatabaseRequirements::OK],
            // PostgreSQL two-part versions against the pgsql tiers.
            ['14.11', '10.0.0', '15.0', DatabaseRequirements::BELOW_RECOMMENDED],
            ['15.0', '10.0.0', '15.0', DatabaseRequirements::OK],
            ['17.2', '10.0.0', '15.0', DatabaseRequirements::OK],
        ];
    }

    public function test_mysql_family_tiers_switch_on_engine(): void
    {
        $mysql = DatabaseRequirements::mysqlFamilyTiers(false);
        $this->assertSame(DatabaseRequirements::MYSQL_MINIMUM, $mysql['minimum']);
        $this->assertSame(DatabaseRequirements::MYSQL_RECOMMENDED, $mysql['recommended']);

        $mariadb = DatabaseRequirements::mysqlFamilyTiers(true);
        $this->assertSame(DatabaseRequirements::MARIADB_MINIMUM, $mariadb['minimum']);
        $this->assertSame(DatabaseRequirements::MARIADB_RECOMMENDED, $mariadb['recommended']);
    }
}
