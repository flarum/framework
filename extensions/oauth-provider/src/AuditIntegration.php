<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider;

use Flarum\Audit\AuditLogger;
use Flarum\OAuthProvider\Models\Client;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Audit log integration for flarum/oauth-provider, wired in behind an
 * Extend\Conditional so it is only active when flarum-audit is enabled.
 *
 * Logs what members do with applications (connecting one, signing in to it
 * again with an earlier approval, disconnecting one or all of them) and what
 * admins do with clients. Everything happens in a request, where
 * flarum/audit's middleware has set the actor.
 */
class AuditIntegration
{
    /**
     * @var string[]
     */
    public static array $actions = [
        'user.oauth_connected',
        'user.oauth_signed_in',
        'user.oauth_disconnected',
        'user.oauth_disconnected_all',
        'oauth_client.created',
        'oauth_client.updated',
        'oauth_client.deleted',
        'oauth_client.secret_rotated',
    ];

    public function __invoke(Container $container): void
    {
        $events = $container->make(Dispatcher::class);

        $events->listen(Event\Authorized::class, function (Event\Authorized $event) {
            AuditLogger::log($event->consented ? 'user.oauth_connected' : 'user.oauth_signed_in', [
                'user_id' => $event->user->id,
                ...$this->client($event->client),
            ]);
        });

        $events->listen(Event\Disconnected::class, function (Event\Disconnected $event) {
            AuditLogger::log('user.oauth_disconnected', [
                'user_id' => $event->user->id,
                ...$this->client($event->client),
            ]);
        });

        $events->listen(Event\DisconnectedAll::class, fn (Event\DisconnectedAll $event) => AuditLogger::log('user.oauth_disconnected_all', ['user_id' => $event->user->id]));

        $events->listen(Event\ClientCreated::class, fn (Event\ClientCreated $event) => AuditLogger::log('oauth_client.created', $this->client($event->client)));
        $events->listen(Event\ClientUpdated::class, fn (Event\ClientUpdated $event) => AuditLogger::log('oauth_client.updated', $this->client($event->client)));
        $events->listen(Event\ClientDeleted::class, fn (Event\ClientDeleted $event) => AuditLogger::log('oauth_client.deleted', $this->client($event->client)));
        $events->listen(Event\ClientSecretRotated::class, fn (Event\ClientSecretRotated $event) => AuditLogger::log('oauth_client.secret_rotated', $this->client($event->client)));
    }

    /**
     * The client's name is kept with its id, so the entry still reads once
     * the client is gone.
     *
     * @return array{client_id: string, client: string|null}
     */
    protected function client(Client $client): array
    {
        return ['client_id' => $client->id, 'client' => $client->name];
    }
}
