<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Command;

use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;
use Flarum\ExtensionManager\Composer\ComposerAdapter;
use Flarum\ExtensionManager\Exception\ComposerRequireFailedException;
use Flarum\ExtensionManager\Exception\ExtensionAlreadyInstalledException;
use Flarum\ExtensionManager\Extension\Event\Installed;
use Flarum\ExtensionManager\RequirePackageValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Input\StringInput;

class RequireExtensionHandler
{
    public function __construct(
        protected ComposerAdapter $composer,
        protected ExtensionManager $extensions,
        protected RequirePackageValidator $validator,
        protected Dispatcher $events
    ) {
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws \Exception
     */
    public function handle(RequireExtension $command): array
    {
        $command->actor->assertAdmin();

        $this->validator->assertValid(['package' => $command->package]);

        $extensionId = Extension::nameToId($command->package);
        $extension = $this->extensions->getExtension($extensionId);

        if (! empty($extension)) {
            throw new ExtensionAlreadyInstalledException($extension);
        }

        $packageName = $command->package;

        // Auto append :* if not requiring a specific version.
        if (! str_contains($packageName, ':')) {
            $packageName .= ':*';
        }

        $output = $this->composer->run(
            new StringInput("require $packageName -W"),
            $command->task ?? null
        );

        if ($output->getExitCode() !== 0) {
            throw new ComposerRequireFailedException($packageName, $output->getContents());
        }

        $this->events->dispatch(
            new Installed($extensionId)
        );

        return ['id' => $extensionId];
    }
}
