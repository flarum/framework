<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\Api\Controller\ShowForumController;
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
        $actor = $request->getAttribute('actor');

        // Expose the complete tag list to clients by adding it as a
        // relationship to the /api endpoint. Since the Forum model
        // doesn't actually have a tags relationship, we will manually load and
        // assign the tags data to it using an event listener.
        $data['tags'] = Tag::whereVisibleTo($actor)
            ->withStateFor($actor)
            ->with([
                'parent',
                'lastPostedDiscussion',
                'lastPostedDiscussion.tags',
                'lastPostedDiscussion.state'
            ])
            ->get();
    }
}
