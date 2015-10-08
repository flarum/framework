<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Migration;

use Flarum\Database\AbstractMigration;

class RenameConfigToSettings extends AbstractMigration
{
    public function up()
    {
        $this->schema->rename('config', 'settings');
    }

    public function down()
    {
        $this->schema->rename('settings', 'config');
    }
}
