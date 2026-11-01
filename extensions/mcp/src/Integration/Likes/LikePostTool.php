<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Likes;

class LikePostTool extends LikeTool
{
    public function name(): string
    {
        return 'like_post';
    }

    public function title(): string
    {
        return 'Like a post';
    }

    public function description(): string
    {
        return 'Like a post as you. Liking a post you already like changes nothing.';
    }

    protected function liked(): bool
    {
        return true;
    }
}
