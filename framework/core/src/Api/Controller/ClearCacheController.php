<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Foundation\Console\AssetsPublishCommand;
use Flarum\Foundation\Console\CacheClearCommand;
use Flarum\Foundation\IOException;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ClearCacheController extends AbstractDeleteController
{
    public function __construct(
        protected CacheClearCommand $command,
        protected AssetsPublishCommand $assetsPublishCommand
    ) {
    }

    /**
     * @throws IOException|\Flarum\User\Exception\PermissionDeniedException|\Symfony\Component\Console\Exception\ExceptionInterface
     */
    protected function delete(ServerRequestInterface $request): void
    {
        RequestUtil::getActor($request)->assertAdmin();

        $exitCode = $this->command->run(
            new ArrayInput([]),
            new NullOutput()
        );

        if ($exitCode !== 0) {
            throw new IOException();
        }

        $exitCode = $this->assetsPublishCommand->run(
            new ArrayInput([]),
            new NullOutput()
        );

        if ($exitCode !== 0) {
            throw new IOException();
        }
    }
}
