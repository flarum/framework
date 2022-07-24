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
use Flarum\PackageManager\Exception\ComposerUpdateFailedException;
use Flarum\PackageManager\Exception\ExtensionNotInstalledException;
use Flarum\PackageManager\Extension\Event\Updated;
use Flarum\PackageManager\Settings\LastUpdateCheck;
use Flarum\PackageManager\UpdateExtensionValidator;
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

        $this->validator->assertValid(['extensionId' => $command->extensionId]);

        $extension = $this->extensions->getExtension($command->extensionId);

        if (empty($extension)) {
            throw new ExtensionNotInstalledException($command->extensionId);
        }

        $output = $this->composer->run(
            new StringInput("require $extension->name:*"),
            $command->task ?? null
        );

        if ($output->getExitCode() !== 0) {
            throw new ComposerUpdateFailedException($extension->name, $output->getContents());
        }

        $this->events->dispatch(
            new Updated($command->actor, $extension)
        );
    }
}
