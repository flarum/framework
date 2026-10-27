<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Composer\InstalledVersions;

// Larastan normally reads this from Application::version(), but Flarum's returns
// Flarum's own version, and we use the illuminate components without
// illuminate/foundation, so there is no Laravel Application to ask. Larastan gates
// both its versioned stub files and some builder methods on this, so a hardcoded
// value silently drops type information as soon as the components move on.
if (! defined('LARAVEL_VERSION')) {
    define('LARAVEL_VERSION', ltrim(InstalledVersions::getPrettyVersion('illuminate/support') ?? '13.0', 'v'));
}

if (! function_exists('database_path')) {
    function database_path($path = ''): string
    {
        return __DIR__."/../../$path";
    }
}

// Larastan needs a booted Flarum application, so the analysis boots a test site,
// installing it first when it has to. Installing drops every table in the
// database it is given, so the site gets a database of its own, a SQLite file in
// its own directory, whatever the environment's DB_* settings point at: for a
// developer, that may be the database they work in.
//
// The directory is keyed by the project and its installed packages, so later
// runs reuse the installation and a dependency change gets a fresh one.
// PHPStan's workers inherit FLARUM_PHPSTAN_TMP_DIR from the main process, so
// they use the same one.
if (! extension_loaded('pdo_sqlite')) {
    throw new RuntimeException('flarum/phpstan installs a Flarum test site into a SQLite database of its own for the analysis, which needs the pdo_sqlite PHP extension.');
}

$tmp = getenv('FLARUM_PHPSTAN_TMP_DIR') ?: rtrim(getenv('FLARUM_TEST_TMP_DIR_LOCAL') ?: getenv('FLARUM_TEST_TMP_DIR') ?: sys_get_temp_dir(), '/')
    .'/phpstan-'.substr(md5(getcwd().serialize(InstalledVersions::getAllRawData())), 0, 12);

if (! is_dir($tmp) && ! @mkdir($tmp, 0755, true) && ! is_dir($tmp)) {
    throw new RuntimeException("Could not create the directory for the analysis's test site: $tmp");
}

putenv("FLARUM_PHPSTAN_TMP_DIR=$tmp");
putenv("FLARUM_TEST_TMP_DIR_LOCAL=$tmp");
putenv('FLARUM_TEST_TMP_DIR');
putenv('DB_DRIVER=sqlite');
putenv('DB_DATABASE=flarum_phpstan');

foreach (['DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD', 'DB_PREFIX', 'DB_SCHEMA'] as $name) {
    putenv($name);
}

// PHPStan loads this file in each parallel worker as well as in the main
// process, and they all share the one site. The testing Bootstrapper only checks
// whether config.php exists, and booting writes caches into the site's storage,
// so each takes its turn: the first one installs, and the rest find it installed.
$lock = fopen("$tmp.lock", 'c');
flock($lock, LOCK_EX);

try {
    $site = (new \Flarum\Testing\integration\Setup\Bootstrapper())->run();
    $site->bootApp();
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
