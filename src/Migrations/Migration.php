<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Migrations;

use Illuminate\Database\Schema\Builder;
use Flarum\Core\Settings\SettingsRepository;

abstract class Migration
{
    /**
     * @var Builder
     */
    protected $schema;

    /**
     * @var SettingsRepository
     */
    protected $settings;

    public function __construct(Builder $schema, SettingsRepository $settings)
    {
        $this->schema = $schema;
        $this->settings = $settings;
    }
}
