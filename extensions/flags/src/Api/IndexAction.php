<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Reports\Api;

use Flarum\Api\Actions\SerializeCollectionAction;
use Flarum\Api\JsonApiRequest;
use Flarum\Reports\Report;
use Tobscure\JsonApi\Document;

class IndexAction extends SerializeCollectionAction
{
    /**
     * @inheritdoc
     */
    public $serializer = 'Flarum\Reports\Api\ReportSerializer';

    /**
     * @inheritdoc
     */
    public $include = [
        'user' => true,
        'post' => true,
        'post.user' => true,
        'post.discussion' => true
    ];

    /**
     * @inheritdoc
     */
    public $link = [];

    protected function data(JsonApiRequest $request, Document $document)
    {
        $actor = $request->actor;

        $actor->reports_read_time = time();
        $actor->save();

        return Report::whereVisibleTo($actor)
            ->with($request->include)
            ->latest('reports.time')
            ->groupBy('post_id')
            ->get();
    }
}
