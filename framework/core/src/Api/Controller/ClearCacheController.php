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
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ClearCacheController extends AbstractDeleteController
{
    /**
     * @var CacheClearCommand
     */
    protected $command;

    /**
     * @var AssetsPublishCommand
     */
    protected $assetsPublishCommand;

    /**
     * @param CacheClearCommand $command
     */
    public function __construct(CacheClearCommand $command, AssetsPublishCommand $assetsPublishCommand)
    {
        $this->command = $command;
        $this->assetsPublishCommand = $assetsPublishCommand;
    }

    /**
     * {@inheritdoc}
     */
    protected function delete(ServerRequestInterface $request)
    {
        RequestUtil::getActor($request)->assertAdmin();

        $exitCode = $this->command->run(
            new ArrayInput([]),
            new NullOutput()
        );

        if ($exitCode !== 0) {
            throw new \Exception('Clearing cache failed. Try running `php flarum cache:clear` from the command line to see more info.');
        }

        $exitCode = $this->assetsPublishCommand->run(
            new ArrayInput([]),
            new NullOutput()
        );

        if ($exitCode !== 0) {
            throw new \Exception('Asset publishing failed. Try running `php flarum assets:publish` from the command line to see more info.');
        }

        return new EmptyResponse(204);
    }
}
