<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\GroupSerializer;
use Flarum\Group\Command\CreateGroup;
use Flarum\Group\Group;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateGroupController extends AbstractCreateController
{
    public ?string $serializer = GroupSerializer::class;

    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function data(ServerRequestInterface $request, Document $document): Group
    {
        return $this->bus->dispatch(
            new CreateGroup(RequestUtil::getActor($request), Arr::get($request->getParsedBody(), 'data', []))
        );
    }
}
