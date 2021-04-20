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

    /**
     * Test database host.
     *
     * @var string
     */
    protected $host;

    /**
     * Test database port.
     *
     * @var int
     */
    protected $port;

    /**
     * Test database name.
     *
     * @var string
     */
    protected $name;

    /**
     * Test database username.
     *
     * @var string
     */
    protected $user;

    /**
     * Test database password.
     *
     * @var string
     */
    protected $pass;

    /**
     * Test database prefix.
     *
     * @var string
     */
    protected $pref;

    /**
     * @var DatabaseConfig
     */
    private $dbConfig;

    public function __construct()
    {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->port = intval(getenv('DB_PORT') ?: 3306);
        $this->name = getenv('DB_DATABASE') ?: 'flarum_test';
        $this->user = getenv('DB_USERNAME') ?: 'root';
        $this->pass = getenv('DB_PASSWORD') ?: 'root';
        $this->pref = getenv('DB_PREFIX') ?: '';
    }

    public function run()
    {
        $tmp = $this->tmpDir();

        echo "Connecting to database $this->name at $this->host:$this->port.\n";
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

        $this->dbConfig = new DatabaseConfig('mysql', $this->host, $this->port, $this->name, $this->user, $this->pass, $this->pref);

        echo "\nWiping DB to ensure clean state\n";
        $this->wipeDb();
        echo "Success! Proceeding to installation...\n";

        $this->setupTmpDir();

        $installation = new Installation(
            new Paths([
                'base' => $tmp,
                'public' => "$tmp/public",
                'storage' => "$tmp/storage",
                'vendor' => getcwd() . '/vendor',
            ])
        );

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
            ->settings(['mail_driver' => 'log'])
            ->extensions([])
            ->build();

        // Run the actual configuration
        $pipeline->run();

        echo "Installation complete\n";
    }

    protected function wipeDb()
    {
        // Reuse the connection step to include version checks
        (new ConnectToDatabase($this->dbConfig, function ($db) {
            // Inspired by Laravel's db:wipe
            $builder = $db->getSchemaBuilder();

            $builder->dropAllTables();
            $builder->dropAllViews();
        }))->run();
    }
}
