<?php

namespace SychO\PackageManager\Command;

use Composer\Command\RequireCommand;
use Composer\Config;
use Composer\Console\Application;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Paths;
use Illuminate\Contracts\Console\Kernel;
use SychO\PackageManager\Extension\ExtensionUtils;
use SychO\PackageManager\Extension\PackageManager;
use SychO\PackageManager\RequirePackageValidator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RequireExtensionHandler
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var PackageManager
     */
    protected $packages;

    /**
     * @var RequireCommand
     */
    protected $command;

    /**
     * @var RequirePackageValidator
     */
    protected $validator;

    public function __construct(ExtensionManager $extensions, PackageManager $packages, RequireCommand $command, RequirePackageValidator $validator)
    {
        $this->extensions = $extensions;
        $this->packages = $packages;
        $this->command = $command;
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

        if (! empty($this->extensions->getExtension($extensionId))) {
            // ... exception
        }

        // $this->packages->requirePackage($command->package);
        $paths = resolve(Paths::class);

        putenv("COMPOSER_HOME={$paths->storage}/.composer");
        putenv("COMPOSER={$paths->base}/composer.json");
        Config::$defaultConfig['vendor-dir'] = $paths->base.'/vendor';

        @ini_set('memory_limit', '1G');
        @set_time_limit(5 * 60);

        $application = new Application();
        $application->setAutoExit(false);

        $output = new BufferedOutput();

        $input = new ArrayInput([
            'command' => 'require',
            'packages' => [$command->package],
            // '--dry-run' => true,
        ]);

        $application->run($input, $output);

        error_log('nandeeeeeeeeeeeeeee');

        throw new \Exception($output->fetch());
    }
}
