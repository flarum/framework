<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Flags\Api\Serializer\FlagSerializer;
use Flarum\Flags\Command\CreateFlag;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateFlagController extends AbstractCreateController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = FlagSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = [
        'post',
        'post.flags',
        'user'
    ];

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        return $this->bus->dispatch(
            new CreateFlag(RequestUtil::getActor($request), Arr::get($request->getParsedBody(), 'data', []))
        );
    }
}
