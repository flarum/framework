<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Api\Resource;

use Carbon\Carbon;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Models\AuthCode;
use Flarum\OAuthProvider\Models\Consent;
use Flarum\OAuthProvider\Models\RefreshToken;
use Illuminate\Database\Eloquent\Builder;
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
        $tokenIds = AccessToken::query()
            ->select('id')
            ->where('user_id', $model->user_id)
            ->where('client_id', $model->client_id);

        RefreshToken::query()->whereIn('access_token_id', $tokenIds)->update(['revoked' => true]);
        AccessToken::query()->where('user_id', $model->user_id)->where('client_id', $model->client_id)->update(['revoked' => true]);
        AuthCode::query()->where('user_id', $model->user_id)->where('client_id', $model->client_id)->update(['revoked' => true]);

        $model->delete();
    }
}
