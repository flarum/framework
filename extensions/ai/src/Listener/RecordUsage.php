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
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\EmbeddingsGenerated;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Records every finished laravel/ai call in the ledger, from any extension or
 * none.
 */
class RecordUsage
{
    public function __construct(
        protected Ledger $ledger,
        protected Attribution $attribution,
        protected LoggerInterface $log
    ) {
    }

    public function handle(AgentPrompted|EmbeddingsGenerated $event): void
    {
        [$provider, $model, $kind] = $event instanceof AgentPrompted
            ? [$event->prompt->provider(), $event->response->meta->model ?? $event->prompt->model, 'text']
            : [$event->provider, $event->model, 'embeddings'];

        if ($provider->name() === AiConfig::UNCONFIGURED) {
            return;
        }

        $actor = $this->attribution->actor();

        try {
            $this->ledger->record(
                $this->attribution->feature(),
                $actor?->exists ? $actor->id : null,
                $provider->name(),
                $provider->driver(),
                $model,
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
