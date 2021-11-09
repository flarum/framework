<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Command;

use Flarum\PackageManager\Composer\ComposerAdapter;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\PackageManager\Event\FlarumUpdated;
use Flarum\PackageManager\Exception\ComposerUpdateFailedException;
use Flarum\PackageManager\LastUpdateCheck;
use Symfony\Component\Console\Input\StringInput;

class MinorFlarumUpdateHandler
{
    /**
     * @var ComposerAdapter
     */
    protected $composer;

    /**
     * @var LastUpdateCheck
     */
    protected $lastUpdateCheck;

    /**
     * @var Dispatcher
     */
    protected $events;

    public function __construct(ComposerAdapter $composer, LastUpdateCheck $lastUpdateCheck, Dispatcher $events)
    {
        $this->composer = $composer;
        $this->lastUpdateCheck = $lastUpdateCheck;
        $this->events = $events;
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws ComposerUpdateFailedException
     */
    public function handle(MinorFlarumUpdate $command)
    {
        $command->actor->assertAdmin();

        $output = $this->composer->run(
            new StringInput("update flarum/* --prefer-dist --no-dev -a --with-all-dependencies")
        );

        if ($output->getExitCode() !== 0) {
            throw new ComposerUpdateFailedException('flarum/*', $output->getContents());
        }

        $this->lastUpdateCheck->forget('flarum/*', true);

        $this->events->dispatch(
            new FlarumUpdated(FlarumUpdated::MINOR)
        );

        return true;
    }
}
