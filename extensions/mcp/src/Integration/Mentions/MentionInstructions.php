<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Mentions;

use Flarum\Mcp\Capability\McpContext;

/**
 * The mention syntax flarum/mentions parses in post and message content.
 */
class MentionInstructions
{
    public function __invoke(McpContext $context): string
    {
        $text = 'In content, @"Display Name"#123 mentions user 123, @"Display Name"#p456 replies to post 456 '
            .'(the name is that post\'s author), and @"Group Name"#g7 mentions group 7.';

        if ($context->settings->get('flarum-mentions.allow_username_format')) {
            $text .= ' @username also mentions a user.';
        }

        return $text;
    }
}
