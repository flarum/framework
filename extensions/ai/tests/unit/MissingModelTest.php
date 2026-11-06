<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Tests\unit;

use Aws\BedrockRuntime\Exception\BedrockRuntimeException;
use Aws\Command;
use Flarum\Ai\Models\ModelHealth;
use GuzzleHttp\Psr7\Response;
use Laravel\Ai\Exceptions\AiException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Bedrock's errors come from the AWS SDK, wrapped by laravel/ai, not as HTTP
 * responses, and Bedrock is reached by region, so there's no wrong address
 * to tell a missing model from.
 */
class MissingModelTest extends TestCase
{
    protected function bedrockError(string $code, int $status, string $message): AiException
    {
        $aws = new BedrockRuntimeException($message, new Command('Converse'), ['code' => $code, 'response' => new Response($status)]);

        return new AiException("AWS Bedrock error for provider [aws]: $message", previous: $aws);
    }

    #[Test]
    public function a_bedrock_model_that_isnt_found_is_missing(): void
    {
        $this->assertTrue(ModelHealth::isMissingModel($this->bedrockError('ResourceNotFoundException', 404, 'The specified resource ARN was not found.'), 'eu.anthropic.claude-x'));
        $this->assertTrue(ModelHealth::isMissingModel($this->bedrockError('ValidationException', 400, 'The provided model identifier is invalid.'), 'eu.anthropic.claude-x'));
    }

    #[Test]
    public function other_bedrock_errors_are_not(): void
    {
        $this->assertFalse(ModelHealth::isMissingModel($this->bedrockError('ValidationException', 400, 'messages: field required'), 'eu.anthropic.claude-x'));
        $this->assertFalse(ModelHealth::isMissingModel($this->bedrockError('ThrottlingException', 429, 'Too many requests'), 'eu.anthropic.claude-x'));
        $this->assertFalse(ModelHealth::isMissingModel($this->bedrockError('AccessDeniedException', 403, 'You don\'t have access to the model with the specified model ID.'), 'eu.anthropic.claude-x'));
    }
}
