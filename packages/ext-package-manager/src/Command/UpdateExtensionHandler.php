<?php

/**
 *
 */

namespace SychO\PackageManager\Command;

use Composer\Console\Application;
use Flarum\Extension\ExtensionManager;
use Flarum\Settings\SettingsRepositoryInterface;
use SychO\PackageManager\Exception\ComposerUpdateFailedException;
use SychO\PackageManager\UpdateExtensionValidator;
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
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(Application $composer, ExtensionManager $extensions, UpdateExtensionValidator $validator, SettingsRepositoryInterface $settings)
    {
        $this->composer = $composer;
        $this->extensions = $extensions;
        $this->validator = $validator;
        $this->settings = $settings;
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

        $lastUpdateCheck = json_decode($this->settings->get('sycho-package-manager.last_update_check', '{}'), true);

        if (isset($lastUpdateCheck['updates']) && ! empty($lastUpdateCheck['updates']['installed'])) {
            $updatesListChanged = false;

            foreach ($lastUpdateCheck['updates']['installed'] as $k => $package) {
                if ($package['name'] === $extension->name) {
                    unset($lastUpdateCheck['updates']['installed'][$k]);
                    $updatesListChanged = true;
                    break;
                }
            }

            if ($updatesListChanged) {
                $lastUpdateCheck['updates']['installed'] = array_values($lastUpdateCheck['updates']['installed']);
                $this->settings->set('sycho-package-manager.last_update_check', json_encode($lastUpdateCheck));
            }
        }

        return true;
    }
}
