<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\GroupSerializer;
use Flarum\Group\Group;
use Flarum\Group\GroupRepository;
use Flarum\Http\RequestUtil;
use Illuminate\Http\Request;
use Tobscure\JsonApi\Document;

class ShowGroupController extends AbstractShowController
{
    public ?string $serializer = GroupSerializer::class;

    public function __construct(
        protected GroupRepository $groups
    ) {
    }

    protected function data(Request $request, Document $document): Group
    {
        $id = $request->query('id');
        $actor = RequestUtil::getActor($request);

        $group = $this->groups->findOrFail($id, $actor);

        return $group;
    }
}
