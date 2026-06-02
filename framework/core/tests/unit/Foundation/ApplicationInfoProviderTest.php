<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Foundation;

use Flarum\Foundation\ApplicationInfoProvider;
use Flarum\Foundation\Config;
use Flarum\Locale\Translator;
use Flarum\Testing\unit\TestCase;
use Flarum\User\SessionManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Database\ConnectionInterface;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use SessionHandlerInterface;

class ApplicationInfoProviderTest extends TestCase
{
    #[Test]
    public function mysql_driver_against_a_mysql_server_reports_no_mismatch()
    {
        $provider = $this->provider('mysql', '8.0.32');

        $this->assertNull($provider->identifyDatabaseDriverMismatch());
    }

    #[Test]
    public function mysql_driver_against_a_mariadb_server_reports_a_mismatch()
    {
        $provider = $this->provider('mysql', '10.11.2-MariaDB-0+deb12u2');

        $this->assertSame('mariadb', $provider->identifyDatabaseDriverMismatch());
    }

    #[Test]
    public function mariadb_driver_against_a_mariadb_server_reports_no_mismatch()
    {
        $provider = $this->provider('mariadb', '10.11.2-MariaDB-0+deb12u2');

        $this->assertNull($provider->identifyDatabaseDriverMismatch());
    }

    #[Test]
    public function mariadb_driver_against_a_mysql_server_reports_a_mismatch()
    {
        $provider = $this->provider('mariadb', '8.0.32');

        $this->assertSame('mysql', $provider->identifyDatabaseDriverMismatch());
    }

    #[Test]
    public function pgsql_driver_is_never_flagged_and_does_not_query_the_server()
    {
        // No version is provided, so the connection mock asserts selectOne() is never called.
        $provider = $this->provider('pgsql', null);

        $this->assertNull($provider->identifyDatabaseDriverMismatch());
    }

    #[Test]
    public function sqlite_driver_is_never_flagged_and_does_not_query_the_server()
    {
        $provider = $this->provider('sqlite', null);

        $this->assertNull($provider->identifyDatabaseDriverMismatch());
    }

    /**
     * Build a provider with a configured driver and the version string the
     * server would report from `select version()`. Pass a null version for
     * drivers that should never trigger a version query (pgsql/sqlite).
     */
    private function provider(string $configuredDriver, ?string $serverVersion): ApplicationInfoProvider
    {
        $cache = m::mock(CacheRepository::class);
        // Execute the cached closure inline so the detection logic is exercised.
        $cache->shouldReceive('remember')
            ->andReturnUsing(fn ($key, $ttl, $callback) => $callback());

        $db = m::mock(ConnectionInterface::class);

        if ($serverVersion !== null) {
            $db->shouldReceive('selectOne')
                ->with('select version() as version')
                ->andReturn((object) ['version' => $serverVersion]);
        } else {
            $db->shouldNotReceive('selectOne');
        }

        $config = new Config([
            'url' => 'http://localhost',
            'database' => ['driver' => $configuredDriver],
        ]);

        return new ApplicationInfoProvider(
            $cache,
            m::mock(Translator::class),
            m::mock(Schedule::class),
            $db,
            $config,
            m::mock(SessionManager::class),
            m::mock(SessionHandlerInterface::class),
            m::mock(Queue::class),
        );
    }
}
