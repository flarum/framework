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
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowForumController extends AbstractShowController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = ForumSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = ['groups'];

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        return [
            'groups' => Group::whereVisibleTo($request->getAttribute('actor'))->get()
        ];
    }
}
