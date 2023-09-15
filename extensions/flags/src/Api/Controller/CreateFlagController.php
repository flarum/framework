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
use Flarum\Flags\Flag;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Tobscure\JsonApi\Document;

class CreateFlagController extends AbstractCreateController
{
    public ?string $serializer = FlagSerializer::class;

    public array $include = [
        'post',
        'post.flags',
        'user'
    ];

    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function data(Request $request, Document $document): Flag
    {
        return $this->bus->dispatch(
            new CreateFlag(RequestUtil::getActor($request), $request->json('data', []))
        );
    }
}
