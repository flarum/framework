<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai;

/**
 * The providers an admin can connect from the admin page. The others laravel/ai
 * supports (Azure, Bedrock, and the embedding, audio and reranking services)
 * need settings that page doesn't ask for, so they go in config.php.
 */
class Drivers
{
    public const ADMIN = [
        'anthropic',
        'deepseek',
        'gemini',
        'groq',
        'mistral',
        'ollama',
        'openai',
        'openai-compatible',
        'openrouter',
        'xai',
    ];

    /**
     * Local models, and compatible servers that may not ask for one.
     */
    public const KEY_OPTIONAL = ['ollama', 'openai-compatible'];

    /**
     * Every other provider falls back to its own API's address.
     */
    public const URL_REQUIRED = ['openai-compatible'];
}
