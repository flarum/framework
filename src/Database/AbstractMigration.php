<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Schema\Builder;

abstract class AbstractMigration
{
    /**
     * @var Builder
     */
    protected $schema;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param Builder                     $schema
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(Builder $schema, SettingsRepositoryInterface $settings)
    {
        $this->schema = $schema;
        $this->settings = $settings;
    }

    /**
     * Run the migrations.
     */
    abstract public function up();

    /**
     * Reverse the migrations.
     */
    abstract public function down();
}
