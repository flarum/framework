<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\integration\Setup;

use Flarum\Foundation\Config;
use Flarum\Foundation\InstalledSite;
use Flarum\Foundation\Paths;
use Flarum\Testing\integration\Extend\BeginTransactionAndSetDatabase;
use Flarum\Testing\integration\Extend\OverrideExtensionManagerForTests;
use Flarum\Testing\integration\Extend\SetSettingsBeforeBoot;
use Flarum\Testing\integration\UsesTmpDir;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;

class Bootstrapper
{
    use UsesTmpDir;

    public ?ConnectionInterface $database = null;

    public function __construct(
        protected array $config = [],
        protected array $extensions = [],
        protected array $settings = [],
        protected array $extenders = []
    ) {
    }

    public function setupOnce(): void
    {
        $tmp = $this->tmpDir();

        if (! file_exists("$tmp/config.php")) {
            $setup = new SetupScript();
            $setup->run();
        }
    }

    public function run(): InstalledSite
    {
        $this->setupOnce();

        $tmp = $this->tmpDir();

        $config = include "$tmp/config.php";

        foreach ($this->config as $key => $value) {
            Arr::set($config, $key, $value);
        }

        $site = new InstalledSite(
            new Paths([
                'base' => $tmp,
                'public' => "$tmp/public",
                'storage' => "$tmp/storage",
                'vendor' => getenv('FLARUM_TEST_VENDOR_PATH') ?: getcwd().'/vendor',
            ]),
            new Config($config)
        );

        $extenders = array_merge([
            new OverrideExtensionManagerForTests($this->extensions),
            new BeginTransactionAndSetDatabase(function (ConnectionInterface $db) {
                $this->database = $db;
            }),
            new SetSettingsBeforeBoot($this->settings),
        ], $this->extenders);

        $site->extendWith($extenders);

        return $site;
    }
}
