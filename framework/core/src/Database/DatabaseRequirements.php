<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Illuminate\Support\Str;

/**
 * Database engine version requirements for this Flarum version.
 *
 * Two tiers per engine:
 *  - MINIMUM: below this Flarum will not run. The installer refuses to proceed
 *    and the admin dashboard shows a blocking error.
 *  - RECOMMENDED: above the minimum but below this runs, but the admin is
 *    warned to encourage upgrading to a modern, supported release.
 *
 * Centralised here so the installer, `ApplicationInfoProvider` and the admin
 * surfaces share a single source of truth.
 */
class DatabaseRequirements
{
    /**
     * Minimum MySQL version. The `JSON` column type, which Flarum relies on,
     * was introduced in MySQL 5.7.8.
     */
    public const MYSQL_MINIMUM = '5.7.8';

    /**
     * Recommended MySQL version — a modern, supported release.
     */
    public const MYSQL_RECOMMENDED = '8.4.0';

    /**
     * Minimum MariaDB version. While JSON support arrived in 10.2.7, Flarum's
     * migrations require features present from 10.3 onwards.
     */
    public const MARIADB_MINIMUM = '10.3.0';

    /**
     * Recommended MariaDB version — a modern, supported release.
     */
    public const MARIADB_RECOMMENDED = '11.8.0';

    public const PGSQL_MINIMUM = '10.0.0';

    /**
     * Recommended PostgreSQL version. PostgreSQL supports each major release for
     * five years; below this a release is (or is about to be) end-of-life, so we
     * warn to encourage staying on a maintained release.
     */
    public const PGSQL_RECOMMENDED = '15.0';

    public const SQLITE_MINIMUM = '3.35.0';

    /**
     * How long an identifier each driver accepts. MySQL and MariaDB count characters;
     * PostgreSQL counts bytes. SQLite imposes no practical limit.
     */
    public const IDENTIFIER_LIMITS = [
        'mysql' => 64,
        'mariadb' => 64,
        'pgsql' => 63,
    ];

    /**
     * Length of the longest index or foreign key name any migration in core or the bundled
     * extensions generates, currently `dialog_message_mentions_group_dialog_message_id_foreign`
     * from flarum/messages.
     *
     * Laravel prepends the table prefix to generated index and foreign key names as well as
     * to table names, so this plus the prefix has to fit inside the driver's limit. Because
     * migrations are immutable and a new installation replays all of them, this is set by
     * migration history rather than by the current schema — renaming a table later does not
     * change it.
     *
     * flarum/testing asserts this against the migrations actually being run, so a migration
     * introducing a longer name fails there rather than in someone's installation.
     */
    public const LONGEST_MIGRATION_IDENTIFIER = 55;

    /**
     * Longest table prefix, in bytes, that leaves room for every identifier the migrations
     * generate. Null where the driver has no meaningful limit.
     */
    public static function maxTablePrefixLength(string $driver): ?int
    {
        $limit = self::IDENTIFIER_LIMITS[$driver] ?? null;

        return $limit === null ? null : $limit - self::LONGEST_MIGRATION_IDENTIFIER;
    }

    /**
     * The result of comparing a version against the tiers.
     */
    public const OK = 'ok';
    public const BELOW_MINIMUM = 'below_minimum';
    public const BELOW_RECOMMENDED = 'below_recommended';

    /**
     * Whether a raw `VERSION()` string reports a MariaDB server.
     */
    public static function isMariaDb(string $rawVersion): bool
    {
        return Str::contains($rawVersion, 'MariaDB', ignoreCase: true);
    }

    /**
     * Extract a dotted numeric version (e.g. "8.0.36" or "11.8.2") from a raw
     * `VERSION()` string.
     *
     * MariaDB reports itself with a legacy "5.5.5-" compatibility prefix in
     * some configurations (e.g. "5.5.5-10.11.6-MariaDB-..."). We strip that
     * prefix before parsing so we read the real MariaDB version.
     *
     * The third segment is optional: since PostgreSQL 10, `SHOW server_version`
     * reports a two-part `major.minor` string (e.g. "16.3"). MySQL and MariaDB
     * always report all three parts, which the greedy match still captures.
     */
    public static function normaliseVersion(string $rawVersion, bool $isMariaDb): ?string
    {
        if ($isMariaDb && str_starts_with($rawVersion, '5.5.5-')) {
            $rawVersion = substr($rawVersion, strlen('5.5.5-'));
        }

        if (preg_match('/(\d+\.\d+(?:\.\d+)?)/', $rawVersion, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * The minimum and recommended versions for a MySQL/MariaDB server.
     *
     * @return array{minimum: string, recommended: string}
     */
    public static function mysqlFamilyTiers(bool $isMariaDb): array
    {
        return $isMariaDb
            ? ['minimum' => self::MARIADB_MINIMUM, 'recommended' => self::MARIADB_RECOMMENDED]
            : ['minimum' => self::MYSQL_MINIMUM, 'recommended' => self::MYSQL_RECOMMENDED];
    }

    /**
     * Compare a normalised version against a tier pair.
     *
     * @return self::OK|self::BELOW_MINIMUM|self::BELOW_RECOMMENDED
     */
    public static function compare(string $version, string $minimum, string $recommended): string
    {
        if (version_compare($version, $minimum, '<')) {
            return self::BELOW_MINIMUM;
        }

        if (version_compare($version, $recommended, '<')) {
            return self::BELOW_RECOMMENDED;
        }

        return self::OK;
    }
}
