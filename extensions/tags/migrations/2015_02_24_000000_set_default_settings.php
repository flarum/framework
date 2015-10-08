<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Migrations;

use Flarum\Database\AbstractMigration;

class SetDefaultSettings extends AbstractMigration
{
    public function up()
    {
        $this->settings->set('flarum-tags.max_primary_tags', '1');
        $this->settings->set('flarum-tags.min_primary_tags', '1');
        $this->settings->set('flarum-tags.max_secondary_tags', '3');
        $this->settings->set('flarum-tags.min_secondary_tags', '0');
    }

    public function down()
    {
        $this->settings->delete('flarum-tags.max_primary_tags');
        $this->settings->delete('flarum-tags.max_secondary_tags');
        $this->settings->delete('flarum-tags.min_primary_tags');
        $this->settings->delete('flarum-tags.min_secondary_tags');
    }
}
