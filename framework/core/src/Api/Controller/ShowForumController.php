<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Group\Group;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowForumController extends AbstractShowController
{
    public ?string $serializer = ForumSerializer::class;

    public array $include = ['groups', 'actor', 'actor.groups'];

    protected function data(ServerRequestInterface $request, Document $document): array
    {
        $actor = RequestUtil::getActor($request);

        return [
            'groups' => Group::whereVisibleTo($actor)->get(),
            'actor' => $actor->isGuest() ? null : $actor
        ];
    }
}
