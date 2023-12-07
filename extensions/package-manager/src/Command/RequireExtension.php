<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Command;

use Flarum\PackageManager\Task\Operation;
use Flarum\User\User;

class RequireExtension extends AbstractActionCommand
{
    public function __construct(
        public User $actor,
        public ?string $package
    ) {
    }

    public function getOperationName(): Operation
    {
        return Operation::EXTENSION_INSTALL;
    }
}
