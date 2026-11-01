<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Nicknames;

use Flarum\Mcp\Capability\McpContext;

class NicknameInstructions
{
    public function __invoke(McpContext $context): ?string
    {
        if ($context->actor->isGuest()) {
            return null;
        }

        return 'Members are shown by their nickname, where they have one. To set yours, if you may, call update_resource on your own user with {"nickname": "..."}.';
    }
}
