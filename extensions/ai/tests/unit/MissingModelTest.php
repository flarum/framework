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
use Flarum\Ai\Models\ProviderModel;
use Flarum\Ai\Models\TemperatureSupport;
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
        $this->assertTrue(ModelHealth::isMissingModel($this->bedrockError('ResourceNotFoundException', 404, 'Model eu.anthropic.claude-x was not found.'), 'eu.anthropic.claude-x'));
        $this->assertTrue(ModelHealth::isMissingModel($this->bedrockError('ResourceNotFoundException', 404, 'This model version has reached the end of its life.'), 'eu.anthropic.claude-x'));
        $this->assertTrue(ModelHealth::isMissingModel($this->bedrockError('ValidationException', 400, 'The provided model identifier is invalid.'), 'eu.anthropic.claude-x'));
    }

    #[Test]
    public function a_bedrock_resource_that_isnt_the_model_is_not(): void
    {
        // AWS doesn't document what a retired model's error says, so only one
        // that's about the model counts: the daily check catches the rest.
        $this->assertFalse(ModelHealth::isMissingModel($this->bedrockError('ResourceNotFoundException', 404, 'Model use case details have not been submitted for this account.'), 'eu.anthropic.claude-x'));
        $this->assertFalse(ModelHealth::isMissingModel($this->bedrockError('ResourceNotFoundException', 404, 'The guardrail gr1 was not found.'), 'eu.anthropic.claude-x'));
        $this->assertFalse(ModelHealth::isMissingModel($this->bedrockError('ResourceNotFoundException', 404, 'The specified resource ARN was not found.'), 'eu.anthropic.claude-x'));
        $this->assertFalse(ModelHealth::isMissingModel($this->bedrockError('ValidationException', 400, "Invocation of model ID anthropic.claude-x with on-demand throughput isn't supported. Retry your request with the ID or ARN of an inference profile that contains this model."), 'anthropic.claude-x'));
    }

    #[Test]
    public function only_models_bedrock_lists_can_be_checked(): void
    {
        $this->assertTrue(ModelHealth::checkable('eu.anthropic.claude-x'));
        $this->assertTrue(ModelHealth::checkable('arn:aws:bedrock:eu-central-1::foundation-model/anthropic.claude-x-v1:0'));
        $this->assertTrue(ModelHealth::checkable('arn:aws:bedrock:eu-central-1:123456789012:inference-profile/eu.anthropic.claude-x-v1:0'));
        $this->assertFalse(ModelHealth::checkable('arn:aws:bedrock:eu-central-1:123456789012:application-inference-profile/abc123'));
        $this->assertFalse(ModelHealth::checkable('arn:aws:bedrock:eu-central-1:123456789012:provisioned-model/abc123'));
        $this->assertFalse(ModelHealth::checkable('arn:aws:bedrock:eu-central-1:123456789012:custom-model/abc123'));
    }

    #[Test]
    public function a_bedrock_refusal_of_a_temperature_is_recognised(): void
    {
        $this->assertTrue(TemperatureSupport::isRefusal($this->bedrockError('ValidationException', 400, 'temperature is not supported for this model.')));
        $this->assertFalse(TemperatureSupport::isRefusal($this->bedrockError('ValidationException', 400, 'messages: field required')));
    }

    #[Test]
    public function a_model_arn_matches_the_model_it_names(): void
    {
        $listed = [new ProviderModel('anthropic.claude-x-v1:0'), new ProviderModel('eu.anthropic.claude-y-v1:0')];

        $this->assertSame('anthropic.claude-x-v1:0', ModelHealth::match($listed, 'arn:aws:bedrock:eu-central-1::foundation-model/anthropic.claude-x-v1:0')?->id);
        $this->assertSame('eu.anthropic.claude-y-v1:0', ModelHealth::match($listed, 'arn:aws:bedrock:eu-central-1:123456789012:inference-profile/eu.anthropic.claude-y-v1:0')?->id);
        $this->assertNull(ModelHealth::match($listed, 'arn:aws:bedrock:eu-central-1::foundation-model/anthropic.claude-z-v1:0'));
    }

    #[Test]
    public function other_bedrock_errors_are_not(): void
    {
        $this->assertFalse(ModelHealth::isMissingModel($this->bedrockError('ValidationException', 400, 'messages: field required'), 'eu.anthropic.claude-x'));
        $this->assertFalse(ModelHealth::isMissingModel($this->bedrockError('ThrottlingException', 429, 'Too many requests'), 'eu.anthropic.claude-x'));
        $this->assertFalse(ModelHealth::isMissingModel($this->bedrockError('AccessDeniedException', 403, 'You don\'t have access to the model with the specified model ID.'), 'eu.anthropic.claude-x'));
    }
}
