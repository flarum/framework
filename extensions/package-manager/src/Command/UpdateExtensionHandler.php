<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Command;

use Composer\Console\Application;
use Flarum\Extension\ExtensionManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\PackageManager\Exception\ComposerUpdateFailedException;
use Flarum\PackageManager\Exception\ExtensionNotInstalledException;
use Flarum\PackageManager\Extension\Event\Updated;
use Flarum\PackageManager\OutputLogger;
use Flarum\PackageManager\UpdateExtensionValidator;
use Flarum\PackageManager\LastUpdateCheck;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class UpdateExtensionHandler
{
    /**
     * @var Application
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

    /**
     * @var OutputLogger
     */
    protected $logger;

    public function __construct(
        Application $composer,
        ExtensionManager $extensions,
        UpdateExtensionValidator $validator,
        LastUpdateCheck $lastUpdateCheck,
        Dispatcher $events,
        OutputLogger $logger)
    {
        $this->composer = $composer;
        $this->extensions = $extensions;
        $this->validator = $validator;
        $this->lastUpdateCheck = $lastUpdateCheck;
        $this->events = $events;
        $this->logger = $logger;
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

        $output = new BufferedOutput();
        $input = new ArrayInput([
            'command' => 'require',
            'packages' => ["$extension->name:*"],
        ]);

        $exitCode = $this->composer->run($input, $output);
        $output = $output->fetch();

        $this->logger->log($input->__toString(), $output, $exitCode);

        if ($exitCode !== 0) {
            throw new ComposerUpdateFailedException($extension->name, $output);
        }

        $this->lastUpdateCheck->forget($extension->name);

        $this->events->dispatch(
            new Updated($extension)
        );

        return true;
    }
}
