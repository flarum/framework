<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Flarum\Tags\Api\Serializer\TagSerializer;
use Flarum\Tags\Command\CreateTag;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateTagController extends AbstractCreateController
{
    public ?string $serializer = TagSerializer::class;

    public array $include = ['parent'];

    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function data(ServerRequestInterface $request, Document $document): Tag
    {
        return $this->bus->dispatch(
            new CreateTag(RequestUtil::getActor($request), Arr::get($request->getParsedBody(), 'data', []))
        );
    }
}
