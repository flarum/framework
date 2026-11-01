<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Tags;

use Flarum\Mcp\Capability\McpContext;

class TagsInstructions
{
    public function __invoke(McpContext $context): ?string
    {
        if ($context->actor->isGuest()) {
            return null;
        }

        return 'To change a discussion\'s tags, if you may, call update_resource on discussions with relationships {"tags": [tag IDs]} (list_tags gives the IDs), listing every tag it should keep.';
    }
}
