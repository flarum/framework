<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai;

use Laravel\Ai\Responses\Data\FinishReason;
use Laravel\Ai\Responses\Data\Step;
use Laravel\Ai\Responses\TextResponse;

/**
 * Whether a reply was stopped by a content filter: a Bedrock connection's
 * guardrail, or a provider's own filter. The reply's text is then what the
 * filter put in its place, if anything, not the model's answer.
 *
 *     $response = $ai->feature('acme-tldr.summaries')->as($actor)->prompt($agent, $text);
 *
 *     if (ContentFilter::applied($response)) {
 *         // Don't show or save it.
 *     }
 */
class ContentFilter
{
    public static function applied(TextResponse $response): bool
    {
        return $response->steps->contains(fn (Step $step) => $step->finishReason === FinishReason::ContentFilter);
    }
}
