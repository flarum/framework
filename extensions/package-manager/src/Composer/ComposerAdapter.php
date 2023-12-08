<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Composer;

use Composer\Config;
use Composer\Console\Application;
use Flarum\Foundation\Paths;
use Flarum\PackageManager\OutputLogger;
use Flarum\PackageManager\Support\Util;
use Flarum\PackageManager\Task\Task;
use Flarum\Settings\SettingsRepositoryInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @internal
 */
class ComposerAdapter
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var OutputLogger
     */
    private $logger;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var SettingsRepositoryInterface
     */
    private $settings;

    public function __construct(Application $application, OutputLogger $logger, Paths $paths, SettingsRepositoryInterface $settings)
    {
        $this->application = $application;
        $this->logger = $logger;
        $this->paths = $paths;
        $this->settings = $settings;
    }

    public function run(InputInterface $input, ?Task $task = null): ComposerOutput
    {
        $this->application->resetComposer();

        // Pre-configure composer
        $this->configureComposer();

        $output = new BufferedOutput();

        // This hack is necessary so that relative path repositories are resolved properly.
        $currDir = getcwd();
        chdir($this->paths->base);
        $exitCode = $this->application->run($input, $output);
        chdir($currDir);

        // @phpstan-ignore-next-line
        $command = Util::readableConsoleInput($input);
        $output = $output->fetch();

        if ($task) {
            $task->update(compact('command', 'output'));
        } else {
            $this->logger->log($command, $output, $exitCode);
        }

        return new ComposerOutput($exitCode, $output);
    }

    public static function setPhpVersion(string $phpVersion)
    {
        Config::$defaultConfig['platform']['php'] = $phpVersion;
    }

    private function configureComposer(): void
    {
        $composerJson = json_decode(file_get_contents($this->paths->base.'/composer.json'), true);
        $dirty = false;

        // Set the minimum stability if not already set.
        $minimumStability = $this->settings->get('flarum-package-manager.minimum_stability');
        $composerMinimumStability = $composerJson['minimum-stability'] ?? null;

        if ($minimumStability && $composerMinimumStability !== $minimumStability) {
            $composerJson['minimum-stability'] = $minimumStability;
            $dirty = true;
        }

        if ($dirty) {
            file_put_contents($this->paths->base.'/composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
}
