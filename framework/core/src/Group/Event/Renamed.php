<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group\Event;

use Flarum\Group\Group;
use Flarum\User\User;

class Renamed
{
    public function __construct(
        public Group $group,
        // The previous names, captured before the rename was saved. Optional/nullable so
        // existing listeners and callers that don't supply them keep working.
        public ?string $oldNameSingular = null,
        public ?string $oldNamePlural = null,
        public ?User $actor = null
    ) {
    }
}
