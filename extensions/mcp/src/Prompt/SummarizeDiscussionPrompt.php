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

class SummarizeDiscussionPrompt extends Prompt
{
    public function name(): string
    {
        return 'summarize_discussion';
    }

    public function title(): ?string
    {
        return 'Summarize a discussion';
    }

    public function description(): ?string
    {
        return 'Read a discussion and summarize it: the question or topic, the main points, and where it stands.';
    }

    public function arguments(): array
    {
        return ['id' => ['description' => 'The discussion ID.', 'required' => true]];
    }

    public function get(array $arguments, McpContext $context): array|string
    {
        $id = (string) ($arguments['id'] ?? '');

        return "Read discussion $id on this forum with the read_discussion tool, following the paging hints until you have read all of it. "
            .'Then summarize it: what it is about, the main points and opinions (attributed to their authors), any decisions or answers, and what is still open. '
            .'Keep it short and quote sparingly.';
    }
}
