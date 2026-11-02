<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Server;

use Mcp\Schema\Content\TextResourceContents;

/**
 * @internal
 */
class ResourceContents
{
    public static function make(string $uri, string $mimeType, mixed $value): TextResourceContents
    {
        return new TextResourceContents($uri, $mimeType, is_string($value) ? $value : Json::encode($value));
    }
}
