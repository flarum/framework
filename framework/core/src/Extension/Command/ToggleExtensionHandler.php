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
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws \Flarum\Extension\Exception\MissingDependenciesException
     * @throws \Flarum\Extension\Exception\DependentExtensionsException
     */
    public function handle(ToggleExtension $command)
    {
        $command->actor->assertAdmin();

        if ($command->enabled) {
            $this->extensions->enable($command->name);
        } else {
            $this->extensions->disable($command->name);
        }
    }
}
