<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Listener;

use Flarum\Ai\AiConfig;
use Flarum\Ai\Usage\Attribution;
use Flarum\Ai\Usage\Ledger;
use Laravel\Ai\Events\AudioGenerated;
use Laravel\Ai\Events\Classified;
use Laravel\Ai\Events\EmbeddingsGenerated;
use Laravel\Ai\Events\ImageGenerated;
use Laravel\Ai\Events\Reranked;
use Laravel\Ai\Events\StepCompleted;
use Laravel\Ai\Events\TranscriptionGenerated;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Records every laravel/ai call, of every kind, from any extension or none.
 *
 * Text is recorded step by step, as each step of an agent call completes:
 * every step is paid for, so a call that fails part-way, fails over to
 * another provider, or is a stream nobody finished reading still has the
 * steps it completed counted.
 *
 * @internal
 */
class RecordUsage
{
    public function __construct(
        protected Ledger $ledger,
        protected Attribution $attribution,
        protected LoggerInterface $log
    ) {
    }

    public function handle(StepCompleted|EmbeddingsGenerated|ImageGenerated|AudioGenerated|TranscriptionGenerated|Reranked|Classified $event): void
    {
        $provider = $event->provider;

        if ($provider->name() === AiConfig::UNCONFIGURED) {
            return;
        }

        $kind = match (true) {
            $event instanceof StepCompleted => 'text',
            $event instanceof EmbeddingsGenerated => 'embeddings',
            $event instanceof ImageGenerated => 'image',
            $event instanceof AudioGenerated => 'audio',
            $event instanceof TranscriptionGenerated => 'transcription',
            $event instanceof Reranked => 'reranking',
            $event instanceof Classified => 'classification',
        };

        try {
            $this->ledger->record(
                $this->attribution->for($event instanceof StepCompleted ? $event->agent : null),
                $provider->name(),
                $provider->driver(),
                // The model that answered, which a provider can report differently from the one asked for.
                ($event instanceof StepCompleted || $event instanceof EmbeddingsGenerated ? $event->response->meta->model : null) ?? $event->model,
                $kind,
                $event->response->usage->inputTokens,
                $event->response->usage->outputTokens
            );
        } catch (Throwable $e) {
            // The call has been made and paid for: losing its record is better
            // than losing the reply.
            $this->log->warning("[flarum/ai] Could not record AI usage: {$e->getMessage()}");
        }
    }
}
