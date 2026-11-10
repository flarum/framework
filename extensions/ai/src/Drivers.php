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
 * supports (Azure, and the embedding, audio and reranking services) need
 * settings that page doesn't ask for, so they go in config.php, as do Bedrock
 * connections that use an access key pair or assume a role.
 *
 * @internal
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
     * Providers offered once the AWS SDK they need is installed.
     */
    public const AWS = ['bedrock'];

    /**
     * Local models, and compatible servers that may not ask for one.
     */
    public const KEY_OPTIONAL = ['ollama', 'openai-compatible'];

    /**
     * Providers reached by region, not address.
     */
    public const REGION_REQUIRED = ['bedrock'];

    /**
     * The providers the admin page can set up now.
     *
     * @return string[]
     */
    public static function admin(?bool $awsSdk = null): array
    {
        $drivers = ($awsSdk ?? self::awsSdkInstalled()) ? [...self::ADMIN, ...self::AWS] : self::ADMIN;

        sort($drivers);

        return $drivers;
    }

    /**
     * Whether a connection can be saved with a provider: one the page offers
     * now, or one it was saved with before the AWS SDK was taken away, so it
     * can still be changed or removed.
     */
    public static function allowed(string $driver, ?string $stored, ?bool $awsSdk = null): bool
    {
        return in_array($driver, self::admin($awsSdk), true)
            || (in_array($driver, self::AWS, true) && $stored === $driver);
    }

    /**
     * The providers that can be set up without a key. Bedrock can when
     * config.php lets it use the server's own AWS credentials: forum admins
     * aren't always who runs the server, and its credentials can reach further.
     *
     * @return string[]
     */
    public static function keyOptional(bool $serverCredentials): array
    {
        return $serverCredentials ? [...self::KEY_OPTIONAL, ...self::AWS] : self::KEY_OPTIONAL;
    }

    public static function awsSdkInstalled(): bool
    {
        return class_exists(\Aws\BedrockRuntime\BedrockRuntimeClient::class);
    }

    /**
     * Every other provider falls back to its own API's address.
     */
    public const URL_REQUIRED = ['openai-compatible'];
}
