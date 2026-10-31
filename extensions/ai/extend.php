<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Ai\AiServiceProvider;
use Flarum\Ai\Api\Controller\ShowUsageController;
use Flarum\Ai\Console\PruneUsageCommand;
use Flarum\Ai\Content;
use Flarum\Ai\Listener;
use Flarum\Extend;
use Flarum\Settings\Event\Deserializing;
use Flarum\Settings\Event\Saved;
use Flarum\Settings\Event\Saving;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\AgentStreamed;
use Laravel\Ai\Events\EmbeddingsGenerated;
use Laravel\Ai\Events\GeneratingEmbeddings;
use Laravel\Ai\Events\PromptingAgent;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less')
        ->content(Content\AdminPayload::class),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\ServiceProvider())
        ->register(AiServiceProvider::class),

    (new Extend\Routes('api'))
        ->get('/ai/usage', 'ai.usage', ShowUsageController::class),

    (new Extend\Console())
        ->command(PruneUsageCommand::class)
        ->schedule(PruneUsageCommand::class, function (ScheduledEvent $event): void {
            $event->daily();
        }),

    (new Extend\ErrorHandling())
        ->status('ai_limit_reached', 429)
        ->status('ai_feature_unavailable', 503),

    (new Extend\Event())
        ->listen(Saving::class, Listener\PrepareSettings::class)
        ->listen(Saved::class, Listener\ReconfigureAi::class)
        ->listen(Deserializing::class, Listener\HideApiKeys::class)
        // Every laravel/ai call, from any extension: limits before it, the
        // ledger after it. AgentStreamed extends AgentPrompted, but listeners
        // only hear the class they're registered for.
        ->listen(PromptingAgent::class, Listener\EnforceLimits::class)
        ->listen(GeneratingEmbeddings::class, Listener\EnforceLimits::class)
        ->listen(AgentPrompted::class, Listener\RecordUsage::class)
        ->listen(AgentStreamed::class, Listener\RecordUsage::class)
        ->listen(EmbeddingsGenerated::class, Listener\RecordUsage::class),
];
