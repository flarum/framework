<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\Api\Controller\ShowForumController;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;

class LoadForumTagsRelationship
{
    /**
     * @param ShowForumController $controller
     * @param $data
     * @param ServerRequestInterface $request
     */
    public function __invoke(ShowForumController $controller, &$data, ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);

        // Expose the complete tag list to clients by adding it as a
        // relationship to the /api endpoint. Since the Forum model
        // doesn't actually have a tags relationship, we will manually load and
        // assign the tags data to it using an event listener.
        $data['tags'] = Tag::query()
            ->where(function ($query) {
                $query
                    ->whereNull('parent_id')
                    ->whereNotNull('position');
            })
            ->union(
                Tag::whereVisibleTo($actor)
                    ->whereNull('parent_id')
                    ->whereNull('position')
                    ->orderBy('discussion_count', 'desc')
                    ->limit(4) // We get one more than we need so the "more" link can be shown.
            )
            ->whereVisibleTo($actor)
            ->withStateFor($actor)
            ->get();
    }
}
