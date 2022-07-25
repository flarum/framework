<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post;

use Flarum\Formatter\Formatter;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Post\Access\ScopePostVisibility;
use Illuminate\Contracts\Container\Container;

class PostServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->extend('flarum.api.throttlers', function (array $throttlers, Container $container) {
            $throttlers['postTimeout'] = $container->make(PostCreationThrottler::class);

            return $throttlers;
        });
    }

    public function boot(Formatter $formatter)
    {
        CommentPost::setFormatter($formatter);

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
