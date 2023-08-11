<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\GroupSerializer;
use Flarum\Group\Command\EditGroup;
use Flarum\Group\Group;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Tobscure\JsonApi\Document;

class UpdateGroupController extends AbstractShowController
{
    public ?string $serializer = GroupSerializer::class;

    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function data(Request $request, Document $document): Group
    {
        $id = $request->query('id');
        $actor = RequestUtil::getActor($request);
        $data = $request->json()->all();

        return $this->bus->dispatch(
            new EditGroup($id, $actor, $data)
        );
    }
}
