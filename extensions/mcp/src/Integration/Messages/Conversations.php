<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Messages;

use Flarum\Mcp\Api\ApiResult;
use Flarum\Mcp\Api\HtmlToText;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Tool\Support\Document;

/**
 * Shapes flarum/messages' `dialogs` and `dialog-messages` for the messaging
 * tools. A dialog is called a conversation here, as the forum calls it.
 *
 * @internal
 */
class Conversations
{
    public const int EXCERPT_LENGTH = 200;

    /**
     * @param array<string, mixed> $dialog
     * @return array<string, mixed>
     */
    public static function conversation(Document $document, array $dialog, McpContext $context): array
    {
        $with = [];
        foreach ((array) ($dialog['relationships']['users']['data'] ?? []) as $identifier) {
            if ((string) ($identifier['id'] ?? '') === (string) $context->actor->id) {
                continue;
            }

            $user = $document->includedFor(['relationships' => ['user' => ['data' => $identifier]]], 'user');

            if ($user) {
                $with[] = (string) Document::attribute($user, 'username');
            }
        }

        return array_filter([
            'id' => (string) $dialog['id'],
            'title' => Document::attribute($dialog, 'title'),
            'with' => $with,
            'unreadCount' => Document::attribute($dialog, 'unreadCount'),
            'lastMessageAt' => Document::attribute($dialog, 'lastMessageAt'),
            'canSendMessage' => Document::attribute($dialog, 'canSendMessage'),
            'url' => static::url($context, (string) $dialog['id']),
        ], fn ($value) => $value !== null);
    }

    /**
     * @param array<string, mixed> $message
     * @return array<string, mixed>
     */
    public static function message(Document $document, array $message, ?int $excerpt = null): array
    {
        $html = Document::attribute($message, 'contentHtml');
        $text = is_string($html) ? HtmlToText::convert($html) : '';

        return array_filter([
            'number' => Document::attribute($message, 'number'),
            'id' => (string) $message['id'],
            'author' => $document->username($message),
            'createdAt' => Document::attribute($message, 'createdAt'),
            'content' => ApiResult::truncate($text, $excerpt ?? ApiResult::MAX_STRING_LENGTH),
        ], fn ($value) => $value !== null);
    }

    public static function url(McpContext $context, string $id): string
    {
        return $context->url->to('forum')->route('messages.dialog', ['id' => $id]);
    }
}
