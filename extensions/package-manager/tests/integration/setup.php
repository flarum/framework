<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\PackageManager\Tests\integration\SetupComposer;

$setup = require __DIR__.'/../../../../php-packages/testing/bootstrap/monorepo.php';

$setup->run();

$setupComposer = new SetupComposer();

$setupComposer->run();
