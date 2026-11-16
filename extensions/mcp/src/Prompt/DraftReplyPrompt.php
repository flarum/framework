<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Prompt;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Prompt;

class DraftReplyPrompt extends Prompt
{
    public function name(): string
    {
        return 'draft_reply';
    }

    public function title(): ?string
    {
        return 'Draft a reply';
    }

    public function description(): ?string
    {
        return 'Read a discussion and draft a reply for you to review before anything is posted.';
    }

    public function arguments(): array
    {
        return [
            'id' => ['description' => 'The discussion ID.', 'required' => true],
            'intent' => ['description' => 'What the reply should say or achieve.', 'required' => true],
        ];
    }

    public function get(array $arguments, McpContext $context): array|string
    {
        $id = (string) ($arguments['id'] ?? '');
        $intent = trim((string) ($arguments['intent'] ?? ''));

        return "Read discussion $id on this forum with the read_discussion tool (the latest posts matter most). "
            ."Then draft a reply that does this: $intent\n\n"
            .'Match the tone of the discussion and use the forum\'s formatting. Show me the draft and wait: '
            .'only post it with reply_to_discussion once I have approved it.';
    }
}
