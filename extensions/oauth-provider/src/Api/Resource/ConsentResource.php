<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Api\Resource;

use Carbon\Carbon;
use Flarum\Api\Context as FlarumContext;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Models\Consent;
use Illuminate\Database\Eloquent\Builder;
use Laminas\Diactoros\Response\EmptyResponse;
use Tobyz\JsonApiServer\Context;

/**
 * The actor's own grants to OAuth clients ("Connected applications").
 * Always scoped to the actor: admins have no special access.
 *
 * @extends Resource\AbstractDatabaseResource<Consent>
 */
class ConsentResource extends Resource\AbstractDatabaseResource
{
    public function routeNamePrefix(): ?string
    {
        return 'oauthProvider';
    }

    public function type(): string
    {
        return 'oauth-consents';
    }

    public function model(): string
    {
        return Consent::class;
    }

    public function scope(Builder $query, Context $context): void
    {
        $tokens = (new AccessToken())->getTable();
        $consents = $query->getModel()->getTable();

        $query
            ->where("$consents.user_id", $context->getActor()->id)
            ->where("$consents.revoked", false)
            ->with('client')
            ->select("$consents.*")
            ->addSelect(['last_used_at' => AccessToken::query()
                ->selectRaw('max(last_used_at)')
                ->whereColumn("$tokens.client_id", "$consents.client_id")
                ->whereColumn("$tokens.user_id", "$consents.user_id"),
            ])
            // Where the most recently used of its tokens was used from.
            ->addSelect(['last_ip_address' => AccessToken::query()
                ->select('last_ip_address')
                ->whereColumn("$tokens.client_id", "$consents.client_id")
                ->whereColumn("$tokens.user_id", "$consents.user_id")
                ->whereNotNull('last_used_at')
                ->orderByDesc('last_used_at')
                ->limit(1),
            ]);
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->authenticated()
                ->defaultSort('-createdAt')
                ->paginate(50),
            Endpoint\Delete::make()
                ->authenticated(),
            // Disconnects every application the actor connected.
            Endpoint\Endpoint::make('revokeAll')
                ->route('DELETE', '/')
                ->authenticated()
                ->action(fn (FlarumContext $context) => Consent::revokeAllFor((int) $context->getActor()->id))
                ->response(fn () => new EmptyResponse(204)),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('clientId')
                ->property('client_id'),
            Schema\Str::make('clientName')
                ->get(fn (Consent $consent) => $consent->client?->name),
            Schema\Str::make('clientLogoUrl')
                ->nullable()
                ->get(fn (Consent $consent) => $consent->client?->logo_uri),
            Schema\Str::make('clientSource')
                ->get(fn (Consent $consent) => $consent->client?->source),
            Schema\Str::make('clientSourceHost')
                ->nullable()
                ->get(fn (Consent $consent) => $consent->client?->sourceHost()),
            Schema\Arr::make('scopes')
                ->get(fn (Consent $consent) => array_values($consent->scopes ?? [])),
            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('lastUsedAt')
                ->nullable()
                ->get(fn (Consent $consent) => $consent->last_used_at ? Carbon::parse($consent->last_used_at) : null),
            Schema\Str::make('lastIpAddress')
                ->nullable()
                ->get(fn (Consent $consent) => $consent->last_ip_address),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
        ];
    }

    /**
     * Revoking a connected application also revokes every outstanding code
     * and token the user granted that client.
     */
    public function delete(object $model, Context $context): void
    {
        /** @var Consent $model */
        $model->revokeAccess();
    }
}
