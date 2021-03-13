<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Post\AbstractEventPost;
use Flarum\Post\MergeableInterface;
use Flarum\Post\Post;
use Flarum\Testing\integration\TestCase;

class PostTest extends TestCase
{
    /**
     * @test
     */
    public function custom_post_type_doesnt_exist_by_default()
    {
        $this->assertArrayNotHasKey('customPost', Post::getModels());
    }

    /**
     * @test
     */
    public function custom_post_type_exists_if_added()
    {
        $this->extend((new Extend\Post)->type(PostTestCustomPost::class));

        // Needed for extenders to be booted
        $this->app();

        $this->assertArrayHasKey('customPost', Post::getModels());
    }
}

class PostTestCustomPost extends AbstractEventPost implements MergeableInterface
{
    /**
     * {@inheritdoc}
     */
    public static $type = 'customPost';

    /**
     * {@inheritdoc}
     */
    public function saveAfter(Post $previous = null)
    {
        $this->save();

        return $this;
    }
}
