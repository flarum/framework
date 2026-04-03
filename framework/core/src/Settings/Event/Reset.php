<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings\Event;

use Flarum\User\User;

class Reset
{
    /**
     * @param User $actor The admin user who triggered the reset.
     * @param string $extensionId The extension ID whose settings were reset (e.g. 'flarum-tags').
     * @param string[] $keys The setting keys that were deleted.
     */
    public function __construct(
        public readonly User $actor,
        public readonly string $extensionId,
        public readonly array $keys
    ) {
    }
}
