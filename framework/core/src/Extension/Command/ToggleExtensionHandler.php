<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension\Command;

use Flarum\Extension\ExtensionManager;

class ToggleExtensionHandler
{
    public function __construct(
        protected ExtensionManager $extensions
    ) {
    }

    public function handle(ToggleExtension $command): void
    {
        $command->actor->assertAdmin();

        if ($command->enabled) {
            $this->extensions->enable($command->name);
        } else {
            $this->extensions->disable($command->name);
        }
    }
}
