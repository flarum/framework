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

// PHPStan analyses in parallel, and every worker loads this file. The testing
// Bootstrapper only guards its install with file_exists() on the config, so
// workers sharing one tmp dir race: one reads config.php while another is still
// writing it, and the include returns false. Give each process its own dir.
$tmp = getenv('FLARUM_TEST_TMP_DIR_LOCAL') ?: getenv('FLARUM_TEST_TMP_DIR') ?: null;

if ($tmp !== null) {
    $tmp = rtrim($tmp, '/').'/phpstan-'.getmypid();

    if (! is_dir($tmp)) {
        mkdir($tmp, 0755, true);
    }

    putenv("FLARUM_TEST_TMP_DIR_LOCAL=$tmp");
    putenv('FLARUM_TEST_TMP_DIR');
}

$site = (new \Flarum\Testing\integration\Setup\Bootstrapper())->run();
$site->bootApp();
