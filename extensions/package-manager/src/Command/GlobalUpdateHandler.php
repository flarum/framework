<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Command;

use Flarum\Bus\Dispatcher as FlarumDispatcher;
use Flarum\Foundation\Config;
use Flarum\PackageManager\Composer\ComposerAdapter;
use Flarum\PackageManager\Event\FlarumUpdated;
use Flarum\PackageManager\Exception\ComposerUpdateFailedException;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Input\ArrayInput;

class GlobalUpdateHandler
{
    /**
     * @var ComposerAdapter
     */
    protected $composer;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var FlarumDispatcher
     */
    protected $commandDispatcher;

    /**
     * @var Config
     */
    protected $config;

    public function __construct(ComposerAdapter $composer, Dispatcher $events, FlarumDispatcher $commandDispatcher, Config $config)
    {
        $this->composer = $composer;
        $this->events = $events;
        $this->commandDispatcher = $commandDispatcher;
        $this->config = $config;
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException|ComposerUpdateFailedException
     */
    public function handle(GlobalUpdate $command)
    {
        $command->actor->assertAdmin();

        $input = [
            'command' => 'update',
            '--prefer-dist' => true,
            '--no-dev' => ! $this->config->inDebugMode(),
            '-a' => true,
            '--with-all-dependencies' => true,
        ];

        dd($this->config->inDebugMode());

        $output = $this->composer->run(
            new ArrayInput($input),
            $command->task ?? null
        );

        if ($output->getExitCode() !== 0) {
            throw new ComposerUpdateFailedException('*', $output->getContents());
        }

        $this->events->dispatch(
            new FlarumUpdated($command->actor, FlarumUpdated::GLOBAL)
        );
    }
}
