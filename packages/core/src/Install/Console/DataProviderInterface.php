<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Console;

use Flarum\Install\AdminUser;
use Flarum\Install\DatabaseConfig;

interface DataProviderInterface
{
    public function getDatabaseConfiguration(): DatabaseConfig;

    public function getBaseUrl();

    public function getAdminUser(): AdminUser;

    public function getSettings();

    public function isDebugMode(): bool;
}
