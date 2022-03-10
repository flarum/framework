<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Command;

use Flarum\PackageManager\Composer\ComposerAdapter;
use Flarum\PackageManager\Exception\ComposerRequireFailedException;
use Flarum\PackageManager\WhyNotValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Input\StringInput;

class WhyNotHandler
{
    /**
     * @var ComposerAdapter
     */
    protected $composer;

    /**
     * @var WhyNotValidator
     */
    protected $validator;

    /**
     * @var Dispatcher
     */
    protected $events;

    public function __construct(ComposerAdapter $composer, WhyNotValidator $validator, Dispatcher $events)
    {
        $this->composer = $composer;
        $this->validator = $validator;
        $this->events = $events;
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws \Exception
     */
    public function handle(WhyNot $command)
    {
        $command->actor->assertAdmin();

        $this->validator->assertValid([
            'package' => $command->package,
            'version' => $command->version
        ]);

        $output = $this->composer->run(
            new StringInput("why-not $command->package $command->version")
        );

        if ($output->getExitCode() !== 0) {
            throw new ComposerRequireFailedException($command->package, $output->getContents());
        }

        return $output->getContents();
    }
}
