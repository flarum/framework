<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Resource;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\ResourceTemplate;
use Flarum\Mcp\Tool\Support\UserReader;

class UserTemplate extends ResourceTemplate
{
    public function __construct(
        protected UserReader $reader,
    ) {
    }

    public function uriTemplate(): string
    {
        return 'flarum://users/{username}';
    }

    public function name(): string
    {
        return 'user';
    }

    public function title(): ?string
    {
        return 'User profile';
    }

    public function description(): ?string
    {
        return 'A member\'s profile, as get_user returns it.';
    }

    public function read(array $variables, McpContext $context): mixed
    {
        return $this->reader->read($context, username: (string) ($variables['username'] ?? ''));
    }
}
