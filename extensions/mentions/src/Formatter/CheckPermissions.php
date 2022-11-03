<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\User\User;
use s9e\TextFormatter\Parser;

class CheckPermissions
{
    public function __invoke(Parser $parser, $content, string $text, ?User $actor): string
    {
        // Check user has `mentionGroups` permission, if not, remove the `GROUPMENTION` tag from the parser.
        if ($actor && $actor->cannot('mentionGroups')) {
            $parser->disableTag('GROUPMENTION');
        }

        return $text;
    }
}
