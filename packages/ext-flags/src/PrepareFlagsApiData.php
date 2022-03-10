<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags;

use Flarum\Api\Controller;
use Flarum\Flags\Api\Controller\CreateFlagController;
use Flarum\Http\RequestUtil;
use Illuminate\Database\Eloquent\Collection;
use Psr\Http\Message\ServerRequestInterface;

class PrepareFlagsApiData
{
    public function __invoke(Controller\AbstractSerializeController $controller, $data, ServerRequestInterface $request)
    {
        // For any API action that allows the 'flags' relationship to be
        // included, we need to preload this relationship onto the data (Post
        // models) so that we can selectively expose only the flags that the
        // user has permission to view.
        if ($controller instanceof Controller\ShowDiscussionController) {
            if ($data->relationLoaded('posts')) {
                $posts = $data->getRelation('posts');
            }
        }

        if ($controller instanceof Controller\ListPostsController) {
            $posts = $data->all();
        }

        if ($controller instanceof Controller\ShowPostController) {
            $posts = [$data];
        }

        if ($controller instanceof CreateFlagController) {
            $posts = [$data->post];
        }

        if (isset($posts)) {
            $actor = RequestUtil::getActor($request);
            $postsWithPermission = [];

            foreach ($posts as $post) {
                if (is_object($post)) {
                    $post->setRelation('flags', null);

                    if ($actor->can('viewFlags', $post->discussion)) {
                        $postsWithPermission[] = $post;
                    }
                }
            }

            if (count($postsWithPermission)) {
                (new Collection($postsWithPermission))
                    ->load('flags', 'flags.user');
            }
        }
    }
}
