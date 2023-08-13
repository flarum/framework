<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Flarum\Tags\Api\Serializer\TagSerializer;
use Flarum\Tags\Command\EditTag;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Tobscure\JsonApi\Document;

class UpdateTagController extends AbstractShowController
{
    public ?string $serializer = TagSerializer::class;

    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function data(Request $request, Document $document): Tag
    {
        $id = $request->route('id');
        $actor = RequestUtil::getActor($request);
        $data = $request->json('data', []);

        return $this->bus->dispatch(
            new EditTag($id, $actor, $data)
        );
    }
}
