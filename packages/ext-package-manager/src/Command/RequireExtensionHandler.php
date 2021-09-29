<?php

/**
 *
 */

namespace SychO\PackageManager\Command;

use Composer\Console\Application;
use Flarum\Extension\ExtensionManager;
use Illuminate\Contracts\Events\Dispatcher;
use SychO\PackageManager\Exception\ComposerRequireFailedException;
use SychO\PackageManager\Exception\ExtensionAlreadyInstalledException;
use SychO\PackageManager\Extension\Event\Installed;
use SychO\PackageManager\Extension\ExtensionUtils;
use SychO\PackageManager\OutputLogger;
use SychO\PackageManager\RequirePackageValidator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RequireExtensionHandler
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
     * @var RequirePackageValidator
     */
    protected $validator;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var OutputLogger
     */
    protected $logger;

    public function __construct(Application $composer, ExtensionManager $extensions, RequirePackageValidator $validator, Dispatcher $events, OutputLogger $logger)
    {
        $this->composer = $composer;
        $this->extensions = $extensions;
        $this->validator = $validator;
        $this->events = $events;
        $this->logger = $logger;
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws \Exception
     */
    public function handle(RequireExtension $command)
    {
        $command->actor->assertAdmin();

        $this->validator->assertValid(['package' => $command->package]);

        $extensionId = ExtensionUtils::nameToId($command->package);
        $extension = $this->extensions->getExtension($extensionId);

        if (! empty($extension)) {
            throw new ExtensionAlreadyInstalledException($extension);
        }

        $output = new BufferedOutput();
        $input = new ArrayInput([
            'command' => 'require',
            'packages' => [$command->package],
        ]);

        $exitCode = $this->composer->run($input, $output);

        $this->logger->log($output->fetch(), $exitCode);

        if ($exitCode !== 0) {
            throw new ComposerRequireFailedException($command->package, $output->fetch());
        }

        $this->events->dispatch(
            new Installed($extensionId)
        );

        return ['id' => $extensionId];
    }
}
