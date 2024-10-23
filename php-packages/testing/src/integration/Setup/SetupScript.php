<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\integration\Setup;

use Flarum\Foundation\Paths;
use Flarum\Install\AdminUser;
use Flarum\Install\BaseUrl;
use Flarum\Install\DatabaseConfig;
use Flarum\Install\Installation;
use Flarum\Install\Steps\ConnectToDatabase;
use Flarum\Testing\integration\UsesTmpDir;

class SetupScript
{
    use UsesTmpDir;

    protected string $driver;
    protected string $host;
    protected int $port;
    protected string $name;
    protected string $user;
    protected string $pass;
    protected string $pref;

    protected DatabaseConfig $dbConfig;

    /**
     * Settings to be applied during installation.
     */
    protected array $settings = ['mail_driver' => 'log'];

    public function __construct()
    {
        $this->driver = getenv('DB_DRIVER') ?: 'mysql';
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->port = intval(getenv('DB_PORT') ?: match ($this->driver) {
            'mysql' => 3306,
            'pgsql' => 5432,
            default => 0,
        });
        $this->name = getenv('DB_DATABASE') ?: 'flarum_test';
        $this->user = getenv('DB_USERNAME') ?: 'root';
        $this->pass = getenv('DB_PASSWORD') ?? 'root';
        $this->pref = getenv('DB_PREFIX') ?: '';
    }

    public function run()
    {
        $tmp = $this->tmpDir();

        if ($this->driver === 'sqlite') {
            echo "Connecting to SQLite database at $this->name.\n";
        } else {
            echo "Connecting to database $this->name at $this->host:$this->port.\n";
        }

        echo "Warning: all tables will be dropped to ensure clean state. DO NOT use your production database!\n";
        echo "Logging in as $this->user with password '$this->pass'.\n";
        echo "Table prefix: '$this->pref'\n";
        echo "\nStoring test config in '$tmp'\n";

        echo "\n\nCancel now if that's not what you want...\n";
        echo "Use the following environment variables for configuration:\n";
        echo "Database: DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_PREFIX\n";
        echo "Test Config: FLARUM_TEST_TMP_DIR or FLARUM_TEST_TMP_DIR_LOCAL\n";

        sleep(4);

        echo "\nOff we go...\n";

        $this->dbConfig = new DatabaseConfig(
            $this->driver,
            $this->host,
            $this->port,
            $this->name,
            $this->user,
            $this->pass,
            $this->pref
        );

        $paths = new Paths([
            'base' => $tmp,
            'public' => "$tmp/public",
            'storage' => "$tmp/storage",
            'vendor' => getenv('FLARUM_TEST_VENDOR_PATH') ?: getcwd().'/vendor',
        ]);

        $this->setupTmpDir();
        $this->dbConfig->prepare($paths);

        echo "\nWiping DB to ensure clean state\n";
        $this->wipeDb($paths);
        echo "Success! Proceeding to installation...\n";

        $installation = new Installation($paths);

        $pipeline = $installation
            ->configPath('config.php')
            ->debugMode(true)
            ->baseUrl(BaseUrl::fromString('http://localhost'))
            ->databaseConfig($this->dbConfig)
            ->adminUser(new AdminUser(
                'admin',
                'password',
                'admin@machine.local'
            ))
            ->settings($this->settings)
            ->extensions([])
            ->build();

        // Run the actual configuration
        $pipeline->run();

        echo "Installation complete\n";
    }

    protected function wipeDb(Paths $paths)
    {
        // Reuse the connection step to include version checks
        (new ConnectToDatabase($this->dbConfig, function ($db) {
            // Inspired by Laravel's db:wipe
            $builder = $db->getSchemaBuilder();

            $builder->dropAllTables();
            $builder->dropAllViews();
        }, $paths->base))->run();
    }

    /**
     * Can be used to add settings to the Flarum installation.
     * Use this only when it is really needed.
     * This can be useful in rare cases where the settings are required to be set
     * already when extensions Extenders are executed. In those cases, setting the
     * settings with the `setting()` method of the `TestCase` will not work.
     *
     * @param string $settings (key => value)
     */
    public function addSettings(array $settings): self
    {
        $this->settings = array_merge($this->settings, $settings);

        return $this;
    }
}
