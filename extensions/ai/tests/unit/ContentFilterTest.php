<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Tests\unit;

use Flarum\Ai\ContentFilter;
use Laravel\Ai\Responses\Data\FinishReason;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Step;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\TextResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ContentFilterTest extends TestCase
{
    protected function response(FinishReason ...$reasons): TextResponse
    {
        return (new TextResponse('Reply', new TextUsage(), new Meta('aws', 'model')))->withSteps(collect(array_map(
            fn (FinishReason $reason) => new Step('Reply', [], [], $reason, new TextUsage(), new Meta('aws', 'model'), '', []),
            $reasons
        )));
    }

    #[Test]
    public function it_says_when_a_guardrail_or_content_filter_stepped_in(): void
    {
        $this->assertTrue(ContentFilter::applied($this->response(FinishReason::ToolCalls, FinishReason::ContentFilter)));
        $this->assertFalse(ContentFilter::applied($this->response(FinishReason::ToolCalls, FinishReason::Stop)));
        $this->assertFalse(ContentFilter::applied($this->response()));
    }
}
