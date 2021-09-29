<?php

/**
 *
 */

namespace SychO\PackageManager\Command;

use Composer\Console\Application;
use Flarum\Extension\ExtensionManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use SychO\PackageManager\Exception\ComposerUpdateFailedException;
use SychO\PackageManager\Extension\Event\Updated;
use SychO\PackageManager\UpdateExtensionValidator;
use SychO\PackageManager\LastUpdateCheck;
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

    public function __construct(Application $composer, ExtensionManager $extensions, UpdateExtensionValidator $validator, LastUpdateCheck $lastUpdateCheck, Dispatcher $events)
    {
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
            // ... exception
        }

        $output = new BufferedOutput();
        $input = new ArrayInput([
            'command' => 'require',
            'packages' => ["$extension->name:*"],
        ]);

        $exitCode = $this->composer->run($input, $output);

        if ($exitCode !== 0) {
            throw new ComposerUpdateFailedException($extension->name, $output->fetch());
        }

        $this->lastUpdateCheck->forget($extension->name);

        $this->events->dispatch(
            new Updated($extension)
        );

        return true;
    }
}
