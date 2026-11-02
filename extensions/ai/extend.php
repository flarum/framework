<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Ai\AiServiceProvider;
use Flarum\Ai\Api\Controller\ShowUsageController;
use Flarum\Ai\Api\ForumResourceFields;
use Flarum\Ai\Console\PruneUsageCommand;
use Flarum\Ai\Content;
use Flarum\Ai\Exception\LimitReachedException;
use Flarum\Ai\Exception\LimitReachedExceptionHandler;
use Flarum\Ai\Listener;
use Flarum\Api\Resource\ForumResource;
use Flarum\Extend;
use Flarum\Settings\Event\Saved;
use Flarum\Settings\Event\Saving;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Laravel\Ai\Events;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less')
        ->content(Content\AdminPayload::class),

    new Extend\Locales(__DIR__.'/locale'),

    // So the admin page has every setting from the start, and doesn't count
    // one that has never been saved as changed.
    (new Extend\Settings())
        ->default('flarum-ai.connections', '{}')
        ->default('flarum-ai.default_connection', '')
        ->default('flarum-ai.features', '{}')
        ->default('flarum-ai.member_daily_tokens', '')
        ->default('flarum-ai.guest_daily_tokens', ''),

    (new Extend\ServiceProvider())
        ->register(AiServiceProvider::class),

    (new Extend\Routes('api'))
        ->get('/ai/usage', 'ai.usage', ShowUsageController::class),

    (new Extend\ApiResource(ForumResource::class))
        ->fields(ForumResourceFields::class),

    (new Extend\Console())
        ->command(PruneUsageCommand::class)
        ->schedule(PruneUsageCommand::class, function (ScheduledEvent $event): void {
            $event->daily();
        }),

    (new Extend\ErrorHandling())
        ->status('ai_feature_disabled', 403)
        ->status('ai_not_configured', 503)
        ->handler(LimitReachedException::class, LimitReachedExceptionHandler::class),

    (new Extend\Event())
        ->listen(Saving::class, Listener\PrepareSettings::class)
        // Keys first, so the connections are rebuilt with them.
        ->listen(Saved::class, Listener\CommitConnectionKeys::class)
        ->listen(Saved::class, Listener\ReconfigureAi::class)
        // Every laravel/ai call, of every kind, from any extension: limits
        // before it, the ledger after it. Listeners only hear the class they're
        // registered for, not its subclasses, so streams have their own.
        ->listen(Events\PromptingAgent::class, Listener\EnforceLimits::class)
        ->listen(Events\StreamingAgent::class, Listener\EnforceLimits::class)
        ->listen(Events\GeneratingEmbeddings::class, Listener\EnforceLimits::class)
        ->listen(Events\GeneratingImage::class, Listener\EnforceLimits::class)
        ->listen(Events\GeneratingAudio::class, Listener\EnforceLimits::class)
        ->listen(Events\GeneratingTranscription::class, Listener\EnforceLimits::class)
        ->listen(Events\Reranking::class, Listener\EnforceLimits::class)
        ->listen(Events\Classifying::class, Listener\EnforceLimits::class)
        ->listen(Events\StepCompleted::class, Listener\RecordUsage::class)
        ->listen(Events\EmbeddingsGenerated::class, Listener\RecordUsage::class)
        ->listen(Events\ImageGenerated::class, Listener\RecordUsage::class)
        ->listen(Events\AudioGenerated::class, Listener\RecordUsage::class)
        ->listen(Events\TranscriptionGenerated::class, Listener\RecordUsage::class)
        ->listen(Events\Reranked::class, Listener\RecordUsage::class)
        ->listen(Events\Classified::class, Listener\RecordUsage::class),
];
