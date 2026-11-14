<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Flags;

use DateTimeInterface;
use Flarum\Discussion\Discussion;
use Flarum\Flags\Flag;
use Flarum\Http\SlugManager;
use Flarum\Mcp\Api\ApiResult;
use Flarum\Mcp\Api\HtmlToText;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\User\User;
use Throwable;

/**
 * The moderation queue: flagged posts, newest first, each with its flags.
 * Read through the model rather than the flags endpoint, which marks the
 * actor's flags as seen; the same visibility rules apply.
 */
class ListFlagsTool extends Tool
{
    public const int MAX_LIMIT = 50;
    public const int EXCERPT_LENGTH = 300;

    /**
     * Flags read at most, to find the newest flagged posts among them.
     */
    protected const int MAX_FLAGS = 500;

    public function __construct(
        protected SlugManager $slugs,
    ) {
    }

    public function name(): string
    {
        return 'list_flags';
    }

    public function title(): string
    {
        return 'List flagged posts';
    }

    public function description(): string
    {
        return 'The moderation queue: posts flagged by members or awaiting approval, newest first, each with an excerpt, its author, '
            .'its discussion and every flag (reason, detail, who raised it). Use dismiss_flags once a post has been dealt with; '
            .'a post awaitingApproval is dealt with by approving it.';
    }

    public function readOnly(): bool
    {
        return true;
    }

    public function isAvailableFor(User $actor): bool
    {
        // Anywhere: seeing flags may be granted for some tags only.
        return $actor->hasPermissionLike('discussion.viewFlags');
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => static::MAX_LIMIT, 'default' => 20, 'description' => 'How many flagged posts.'],
            ],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $limit = max(1, min(static::MAX_LIMIT, (int) ($arguments['limit'] ?? 20)));

        $flags = Flag::query()
            ->whereVisibleTo($context->actor)
            ->with(['post.discussion', 'post.user', 'user'])
            ->orderByDesc('flags.created_at')
            ->limit(static::MAX_FLAGS)
            ->get();

        $posts = [];

        foreach ($flags as $flag) {
            $post = $flag->post;

            if (! $post) {
                continue;
            }

            $posts[$post->id] ??= $this->post($post, $context);

            if ($flag->type === 'approval') {
                $posts[$post->id]['awaitingApproval'] = true;
            }

            $posts[$post->id]['flags'][] = array_filter([
                'type' => $flag->type,
                'reason' => $flag->reason,
                'detail' => $flag->reason_detail ?: null,
                'by' => $flag->user?->username,
                'createdAt' => $flag->created_at?->format(DateTimeInterface::RFC3339),
            ], fn ($value) => $value !== null);
        }

        return ['data' => array_slice(array_values($posts), 0, $limit)];
    }

    /**
     * @return array<string, mixed>
     */
    protected function post(Post $post, McpContext $context): array
    {
        /** @var Discussion|null $discussion */
        $discussion = $post->discussion;
        $url = $discussion
            ? $context->url->to('forum')->route('discussion', ['id' => $this->slugs->forResource(Discussion::class)->toSlug($discussion)]).'/'.$post->number
            : null;

        return array_filter([
            'postId' => (string) $post->id,
            'number' => $post->number,
            'author' => $post->user?->username,
            'excerpt' => $post instanceof CommentPost ? $this->excerpt($post, $context) : null,
            'discussion' => $discussion ? ['id' => (string) $discussion->id, 'title' => $discussion->title] : null,
            'url' => $url,
            'awaitingApproval' => $post->is_approved === false ? true : null,
            'flags' => [],
        ], fn ($value) => $value !== null);
    }

    protected function excerpt(CommentPost $post, McpContext $context): string
    {
        try {
            $html = $post->formatContent($context->request);
        } catch (Throwable) {
            return '';
        }

        return ApiResult::truncate(HtmlToText::convert($html), static::EXCERPT_LENGTH);
    }
}
