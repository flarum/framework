<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Foundation\Console\CacheClearCommand;
use Flarum\User\AssertPermissionTrait;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Zend\Diactoros\Response\EmptyResponse;

class ClearCacheController extends AbstractDeleteController
{
    use AssertPermissionTrait;

    /**
     * @var CacheClearCommand
     */
    protected $command;

    /**
     * @param CacheClearCommand $command
     */
    public function __construct(CacheClearCommand $command)
    {
        $this->command = $command;
    }

    /**
     * {@inheritdoc}
     */
    protected function delete(ServerRequestInterface $request)
    {
        $this->assertAdmin($request->getAttribute('actor'));

        $this->command->run(
            new ArrayInput([]),
            new NullOutput()
        );

        return new EmptyResponse(204);
    }
}
