<?php

namespace SychO\PackageManager\Command;

use Composer\Console\Application;
use Flarum\Extension\ExtensionManager;
use SychO\PackageManager\Exception\ComposerRequireFailedException;
use SychO\PackageManager\Extension\ExtensionUtils;
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

    public function __construct(Application $composer, ExtensionManager $extensions, RequirePackageValidator $validator)
    {
        $this->composer = $composer;
        $this->extensions = $extensions;
        $this->validator = $validator;
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
            // ... exception
        }

        $output = new BufferedOutput();
        $input = new ArrayInput([
            'command' => 'require',
            'packages' => [$command->package],
        ]);

        $exitCode = $this->composer->run($input, $output);

        if ($exitCode !== 0) {
            throw new ComposerRequireFailedException($command->package, $output->fetch());
        }

        return ['id' => $extensionId];
    }
}
