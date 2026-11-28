<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Post\Post as PostModel;
use Illuminate\Contracts\Container\Container;

class Post implements ExtenderInterface
{
    private array $postTypes = [];

    private array $visibilityAttributes = [];

    /**
     * Register a new post type. This is generally done for custom 'event posts',
     * such as those that appear when a discussion is renamed.
     *
     * @param string $postType: The ::class attribute of the custom Post type that is being added.
     */
    public function type(string $postType): self
    {
        $this->postTypes[] = $postType;

        return $this;
    }

    /**
     * Register an attribute that decides whether a post is visible, such as a
     * flag that hides it from most users. A change to it is saved under the
     * discussion lock, so that counters kept per post stay correct when
     * visibility changes at the same time as another write.
     *
     * A change made in an `updating` listener is not covered by the lock.
     *
     * @param string $attribute: The name of the post column.
     */
    public function visibilityAttribute(string $attribute): self
    {
        $this->visibilityAttributes[] = $attribute;

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        foreach ($this->postTypes as $postType) {
            PostModel::setModel($postType::$type, $postType);
        }

        foreach ($this->visibilityAttributes as $attribute) {
            PostModel::registerVisibilityAttribute($attribute);
        }
    }
}
