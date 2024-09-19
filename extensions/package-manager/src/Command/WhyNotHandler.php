<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Command;

use Flarum\ExtensionManager\Composer\ComposerAdapter;
use Flarum\ExtensionManager\Exception\ComposerRequireFailedException;
use Flarum\ExtensionManager\WhyNotValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Input\StringInput;

class WhyNotHandler
{
    public function __construct(
        protected ComposerAdapter $composer,
        protected WhyNotValidator $validator,
        protected Dispatcher $events
    ) {
    }

    /**
     * @throws \Flarum\User\Exception\PermissionDeniedException
     * @throws \Exception
     */
    public function handle(WhyNot $command): array
    {
        $command->actor->assertAdmin();

        $this->validator->assertValid([
            'package' => $command->package,
            'version' => $command->version
        ]);

        $output = $this->composer->run(
            new StringInput("why-not $command->package $command->version"),
            $command->task ?? null
        );

        if ($output->getExitCode() !== 0) {
            throw new ComposerRequireFailedException($command->package, $output->getContents());
        }

        return ['reason' => $output->getContents()];
    }
}
