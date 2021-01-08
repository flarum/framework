<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Testing\integration\ConfigureSetup;

require __DIR__.'/../../vendor/autoload.php';

$setup = new ConfigureSetup(__DIR__.'/../../vendor');

$setup->run();
