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
use Flarum\Mcp\Exception\ToolError;
use Flarum\Mcp\Tool\Support\DiscussionReader;

/**
 * The first page of a discussion. The pages after it are resources too (see
 * DiscussionPostsTemplate and DiscussionPostsBeforeTemplate), and the paging
 * hints of each page are the URIs of its neighbours.
 */
class DiscussionTemplate extends ResourceTemplate
{
    public function __construct(
        protected DiscussionReader $reader,
    ) {
    }

    public function uriTemplate(): string
    {
        return 'flarum://discussions/{id}';
    }

    public function name(): string
    {
        return 'discussion';
    }

    public function title(): ?string
    {
        return 'Discussion';
    }

    public function description(): ?string
    {
        return 'A discussion with its first 20 posts, as read_discussion returns it. paging.next.uri is the resource with the posts after them.';
    }

    public function read(array $variables, McpContext $context): mixed
    {
        return $this->page($context, $variables);
    }

    /**
     * A page as `read_discussion` gives it, with the paging hints as resource URIs.
     *
     * @param array<string, string> $variables
     * @return array<string, mixed>
     */
    protected function page(McpContext $context, array $variables, ?int $offset = null, ?int $before = null): array
    {
        $output = $this->reader->read($context, (string) ($variables['id'] ?? ''), offset: $offset, before: $before);
        $base = 'flarum://discussions/'.$output['discussion']['id'].'/posts/';

        $previous = $output['paging']['previous']['before'] ?? null;
        $next = $output['paging']['next']['offset'] ?? null;

        $output['paging']['previous'] = $previous === null ? null : ['uri' => $base.'before/'.$previous];
        $output['paging']['next'] = $next === null ? null : ['uri' => $base.$next];

        return $output;
    }

    /**
     * @param array<string, string> $variables
     */
    protected function offset(array $variables, string $name): int
    {
        $value = (string) ($variables[$name] ?? '');

        if (! ctype_digit($value)) {
            throw new ToolError(sprintf('"%s" is not an offset.', $value));
        }

        return (int) $value;
    }
}
