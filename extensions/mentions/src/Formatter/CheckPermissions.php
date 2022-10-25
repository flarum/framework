<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use s9e\TextFormatter\Parser;

class CheckPermissions
{
    public function __invoke(Parser $parser, mixed $content, string $text, ?ServerRequestInterface $request): string
    {
        // Check user has `mentionGroups` permission, if not, remove it from the parser

        if ($request) {
            dd($request);
            $actor = RequestUtil::getActor($request);
            if ($actor->cannot('mentionGroups')) {
                $parser->disableTag('GROUPMENTION');
            }
        }

        return $text;
    }
}
