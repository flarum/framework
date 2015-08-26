<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Actions\Forum;

use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Flarum\Core\Groups\Group;
use Tobscure\JsonApi\Document;

class ShowAction extends SerializeResourceAction
{
    /**
     * @inheritdoc
     */
    public $serializer = 'Flarum\Api\Serializers\ForumSerializer';

    /**
     * @inheritdoc
     */
    public $include = [
        'groups' => true
    ];

    /**
     * @inheritdoc
     */
    public $link = [];

    /**
     * @inheritdoc
     */
    public $limitMax = 50;

    /**
     * @inheritdoc
     */
    public $limit = 20;

    /**
     * @inheritdoc
     */
    public $sortFields = [];

    /**
     * @inheritdoc
     */
    public $sort;

    /**
     * Get the forum, ready to be serialized and assigned to the JsonApi
     * response.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Flarum\Core\Forum
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        $forum = app('flarum.forum');

        $forum->groups = Group::whereVisibleTo($request->actor)->get();

        return $forum;
    }
}
