<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post;

use DateTime;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Post\Access\ScopePostVisibility;

class PostServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->extend('flarum.api.throttlers', function ($throttlers) {
            $throttlers['postTimeout'] = function ($request) {
                if (! in_array($request->getAttribute('routeName'), ['discussions.create', 'posts.create'])) {
                    return;
                }

                $actor = $request->getAttribute('actor');

                if ($actor->can('postWithoutThrottle')) {
                    return false;
                }

                if (Post::where('user_id', $actor->id)->where('created_at', '>=', new DateTime('-10 seconds'))->exists()) {
                    return true;
                }
            };

            return $throttlers;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        CommentPost::setFormatter($this->container->make('flarum.formatter'));

        $this->setPostTypes();

        Post::registerVisibilityScoper(new ScopePostVisibility(), 'view');
    }

    protected function setPostTypes()
    {
        $models = [
            CommentPost::class,
            DiscussionRenamedPost::class
        ];

        foreach ($models as $model) {
            Post::setModel($model::$type, $model);
        }
    }
}
