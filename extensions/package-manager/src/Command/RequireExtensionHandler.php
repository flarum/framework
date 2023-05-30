<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Command;

use Flarum\Extension\ExtensionManager;
use Flarum\PackageManager\Composer\ComposerAdapter;
use Flarum\PackageManager\Exception\ComposerRequireFailedException;
use Flarum\PackageManager\Exception\ExtensionAlreadyInstalledException;
use Flarum\PackageManager\Extension\Event\Installed;
use Flarum\PackageManager\Extension\ExtensionUtils;
use Flarum\PackageManager\RequirePackageValidator;
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

    public function handle(RequireExtension $command): array
    {
        $command->actor->assertAdmin();

        $this->validator->assertValid(['package' => $command->package]);

        $extensionId = ExtensionUtils::nameToId($command->package);
        $extension = $this->extensions->getExtension($extensionId);

        if (! empty($extension)) {
            throw new ExtensionAlreadyInstalledException($extension);
        }

        $packageName = $command->package;

        // Auto append :* if not requiring a specific version.
        if (strpos($packageName, ':') === false) {
            $packageName .= ':*';
        }

        $output = $this->composer->run(
            new StringInput("require $packageName"),
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
