<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Messages;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;
use Flarum\Mcp\Exception\ToolError;
use Flarum\Mcp\Tool\Support\Document;

/**
 * Sends a private message, in an existing conversation or to a member. Who
 * may message whom is decided by flarum/messages' own endpoint and policies.
 */
class SendMessageTool extends Tool
{
    public function name(): string
    {
        return 'send_message';
    }

    public function title(): string
    {
        return 'Send a private message';
    }

    public function description(): string
    {
        return 'Send a private message as you, either in an existing conversation (conversationId) or to a member (to, their username), '
            .'which continues your conversation with them if there is one. Confirm the recipient and content with the user first. '
            .'Content uses the forum\'s formatting (Markdown or BBCode, as enabled).';
    }

    public function readOnly(): bool
    {
        return false;
    }

    public function idempotent(): bool
    {
        return false;
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'conversationId' => ['type' => ['string', 'integer'], 'description' => 'Reply in this conversation.'],
                'to' => ['type' => 'string', 'description' => 'Or: the username of the member to write to.'],
                'content' => ['type' => 'string', 'minLength' => 1],
            ],
            'required' => ['content'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $conversationId = trim((string) ($arguments['conversationId'] ?? ''));
        $to = trim((string) ($arguments['to'] ?? ''));

        if (($conversationId === '') === ($to === '')) {
            throw new ToolError('Give exactly one of conversationId (to reply in a conversation) or to (to write to a member).');
        }

        $attributes = ['content' => (string) $arguments['content']];
        $relationships = [];

        if ($conversationId !== '') {
            $relationships['dialog'] = ['data' => ['type' => 'dialogs', 'id' => $conversationId]];
        } else {
            // `bySlug` resolves through the forum's user slug driver (username by default).
            $recipient = Document::from($context->api->show('users', $to, ['bySlug' => '1']))->one();
            $attributes['users'] = [['id' => (string) $recipient['id']]];
        }

        $message = Document::from($context->api->create('dialog-messages', $attributes, $relationships))->one();
        $dialog = Document::related($message, 'dialog');

        return array_filter([
            'conversationId' => $dialog['id'] ?? null,
            'messageId' => (string) $message['id'],
            'number' => Document::attribute($message, 'number'),
            'url' => $dialog ? Conversations::url($context, $dialog['id']) : null,
        ], fn ($value) => $value !== null);
    }
}
