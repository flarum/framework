<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Schema\Builder;

/** @internal */
readonly class MigrationContext
{
    /** @param string[] $enabledExtensionIds */
    public function __construct(
        public Builder $schema,
        public array $enabledExtensionIds,
        public SettingsRepositoryInterface $settings
    ) {
    }
}
