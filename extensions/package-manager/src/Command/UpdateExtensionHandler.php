<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Command;

use Flarum\Extension\ExtensionManager;
use Flarum\ExtensionManager\Composer\ComposerAdapter;
use Flarum\ExtensionManager\Exception\ComposerUpdateFailedException;
use Flarum\ExtensionManager\Exception\ExtensionNotInstalledException;
use Flarum\ExtensionManager\Extension\Event\Updated;
use Flarum\ExtensionManager\Settings\LastUpdateCheck;
use Flarum\ExtensionManager\UpdateExtensionValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Input\StringInput;

class UpdateExtensionHandler
{
    /**
     * @var ComposerAdapter
     */
    protected $composer;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var UpdateExtensionValidator
     */
    protected $validator;

    /**
     * @var LastUpdateCheck
     */
    protected $lastUpdateCheck;

    /**
     * @var Dispatcher
     */
    protected $events;

    public function __construct(
        ComposerAdapter $composer,
        ExtensionManager $extensions,
        UpdateExtensionValidator $validator,
        LastUpdateCheck $lastUpdateCheck,
        Dispatcher $events
    ) {
        $this->composer = $composer;
        $this->extensions = $extensions;
        $this->validator = $validator;
        $this->lastUpdateCheck = $lastUpdateCheck;
        $this->events = $events;
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws \Exception
     */
    public function handle(UpdateExtension $command)
    {
        $command->actor->assertAdmin();

        $this->validator->assertValid([
            'extensionId' => $command->extensionId,
            'updateMode' => $command->updateMode,
        ]);

        $extension = $this->extensions->getExtension($command->extensionId);

        if (empty($extension)) {
            throw new ExtensionNotInstalledException($command->extensionId);
        }

        // In situations where an extension was locked to a specific version,
        // a hard update mode is useful to allow removing the locked version and
        // instead requiring the latest version.
        // Another scenario could be when requiring a specific version range, for example 0.1.*,
        // the admin might either want to update to the latest version in that range, or to the latest version overall (0.2.0).
        if ($command->updateMode === 'soft') {
            $input = "update $extension->name";
        } else {
            $input = "require $extension->name:*";
        }

        $output = $this->composer->run(
            new StringInput($input),
            $command->task ?? null,
            true
        );

        if ($output->getExitCode() !== 0) {
            throw new ComposerUpdateFailedException($extension->name, $output->getContents());
        }

        $this->events->dispatch(
            new Updated($command->actor, $extension)
        );
    }
}
