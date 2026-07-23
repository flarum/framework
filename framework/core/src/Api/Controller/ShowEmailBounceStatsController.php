<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Carbon\Carbon;
use Flarum\Http\RequestUtil;
use Flarum\Mail\EmailBounceEvent;
use Flarum\Mail\HandlesWebhooks;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Returns counts of bounce/complaint events grouped by how recently they
 * occurred. These are true event counts from the historical log, so they
 * reflect volume over each window even after affected users fix their address.
 */
class ShowEmailBounceStatsController implements RequestHandlerInterface
{
    public function __construct(
        protected Container $container,
        protected SettingsRepositoryInterface $settings,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $now = Carbon::now();

        return new JsonResponse([
            'hour' => $this->countSince($now->copy()->subHour()),
            'week' => $this->countSince($now->copy()->subWeek()),
            'month' => $this->countSince($now->copy()->subMonth()),
            'total' => EmailBounceEvent::count(),
            // Whether the inbound return path is actually usable: the driver
            // can receive webhooks AND a signing secret is configured (without
            // it, incoming webhooks are rejected and no bounces are recorded).
            'configured' => $this->webhookConfigured(),
            // Users still flagged as bounced right now (unresolved).
            'affected' => User::whereNotNull('email_bounced_at')->count(),
            // Users who had a bounce event but are no longer flagged — i.e.
            // they fixed their address. Distinct users, from the event log.
            'recovered' => EmailBounceEvent::query()
                ->whereNotNull('user_id')
                ->whereIn('user_id', User::whereNull('email_bounced_at')->select('id'))
                ->distinct()
                ->count('user_id'),
        ]);
    }

    protected function countSince(Carbon $since): int
    {
        return EmailBounceEvent::where('created_at', '>=', $since)->count();
    }

    protected function webhookConfigured(): bool
    {
        $driver = $this->container->make('flarum.mail.configured_driver');

        // The driver decides what "configured" means for its provider (Mailgun
        // needs a signing key; Postmark needs nothing beyond IP verification).
        return $driver instanceof HandlesWebhooks
            && $driver->webhookConfigured($this->settings);
    }
}
