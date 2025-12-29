<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\SystemInfoSerializer;
use Flarum\Foundation\Console\InfoCommand;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tobscure\JsonApi\Document;

class ShowInfoController extends AbstractShowController
{
    /**
     * @var InfoCommand
     */
    protected $command;

    /**
     * {@inheritdoc}
     */
    public $serializer = SystemInfoSerializer::class;

    /**
     * @param InfoCommand $command
     */
    public function __construct(InfoCommand $command)
    {
        $this->command = $command;
    }

    /**
     * {@inheritdoc}
     * @throws \Flarum\User\Exception\PermissionDeniedException
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        RequestUtil::getActor($request)->assertAdmin();

        $output = new BufferedOutput();

        $this->command->run(
            new ArrayInput([]),
            $output
        );

        $result = new \stdClass();
        $result->content = $output->fetch();

        return $result;
    }
}
