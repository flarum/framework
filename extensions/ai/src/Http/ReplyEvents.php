<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Http;

use Closure;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\TextDelta;

/**
 * Sends an agent's reply to the browser as it's written, through core's
 * {@see \Flarum\Http\EventStream}:
 *
 *     return resolve(EventStream::class)->response(function (Closure $send) use ($ai, $actor, $text) {
 *         $reply = $ai->feature('acme-tldr.summaries')->as($actor)->stream(new Summariser(), $text);
 *         $usage = ReplyEvents::relay($reply, $send);
 *         $send('done', ['tokens' => $usage->inputTokens + $usage->outputTokens]);
 *     });
 *
 * In the browser, each `text` event's `delta` is the text written since the last.
 */
class ReplyEvents
{
    /**
     * Sends the reply as `text` events as it arrives.
     *
     * @param Closure(string $type, array<string, mixed> $data=): void $send
     * @return TextUsage The tokens the reply used.
     */
    public static function relay(StreamableAgentResponse $reply, Closure $send): TextUsage
    {
        $usage = new TextUsage();

        foreach ($reply as $event) {
            if ($event instanceof TextDelta && $event->delta !== '') {
                $send('text', ['delta' => $event->delta]);
            } elseif ($event instanceof StreamEnd) {
                $usage = $usage->add($event->usage);
            }
        }

        return $usage;
    }
}
