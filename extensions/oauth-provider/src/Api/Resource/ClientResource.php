<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Api\Resource;

use Carbon\Carbon;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Flarum\Locale\TranslatorInterface;
use Flarum\OAuthProvider\ClientRegistration\ClientMetadata;
use Flarum\OAuthProvider\ClientRegistration\ClientMetadataException;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Models\AuthCode;
use Flarum\OAuthProvider\Models\Client;
use Flarum\OAuthProvider\Models\RefreshToken;
use Flarum\OAuthProvider\Scope\ScopeRegistry;
use Illuminate\Support\Str;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends Resource\AbstractDatabaseResource<Client>
 */
class ClientResource extends Resource\AbstractDatabaseResource
{
    public function __construct(
        protected ScopeRegistry $scopes,
        protected TranslatorInterface $translator,
    ) {
    }

    public function routeNamePrefix(): ?string
    {
        return 'oauthProvider';
    }

    public function type(): string
    {
        return 'oauth-provider-clients';
    }

    public function model(): string
    {
        return Client::class;
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->authenticated()
                ->admin(),
            Endpoint\Show::make()
                ->authenticated()
                ->admin(),
            Endpoint\Create::make()
                ->authenticated()
                ->admin(),
            Endpoint\Update::make()
                ->authenticated()
                ->admin(),
            Endpoint\Delete::make()
                ->authenticated()
                ->admin(),
            Endpoint\Endpoint::make('rotateSecret')
                ->route('POST', '/{id}/rotate-secret')
                ->authenticated()
                ->admin()
                ->action(function (Context $context): object {
                    /** @var Client $client */
                    $client = $context->model;

                    if (! $client->confidential) {
                        throw new ValidationException([
                            'client' => 'Public clients have no secret to rotate.',
                        ]);
                    }

                    $plainSecret = Str::random(40);
                    $client->secret = hash('sha256', $plainSecret);
                    $client->updated_at = Carbon::now();
                    $client->save();

                    // Invalidate all tokens previously issued to this client —
                    // a rotation should lock out anyone holding the old secret.
                    AuthCode::query()->where('client_id', $client->id)->update(['revoked' => true]);
                    $accessTokenIds = AccessToken::query()
                        ->where('client_id', $client->id)
                        ->pluck('id');
                    AccessToken::query()->where('client_id', $client->id)->update(['revoked' => true]);
                    RefreshToken::query()
                        ->whereIn('access_token_id', $accessTokenIds)
                        ->update(['revoked' => true]);

                    $client->plainSecret = $plainSecret;

                    return $client;
                }),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('name')
                ->writable()
                ->requiredOnCreate()
                ->maxLength(255),
            Schema\Arr::make('redirectUris')
                ->writable()
                ->requiredOnCreate()
                ->rule(fn () => $this->validateRedirectUris(...))
                ->property('redirect_uris'),
            Schema\Arr::make('scopes')
                ->writable()
                ->nullable()
                ->rule(fn () => $this->validateScopes(...)),
            Schema\Boolean::make('confidential')
                ->writable(),
            Schema\Boolean::make('revoked')
                ->writable(),
            Schema\Str::make('plainSecret')
                ->nullable()
                ->get(fn (Client $client) => $client->plainSecret),
            Schema\Str::make('source'),
            Schema\Str::make('logoUri')
                ->nullable()
                ->property('logo_uri'),
            Schema\Str::make('clientUri')
                ->nullable()
                ->property('client_uri'),
            Schema\DateTime::make('metadataExpiresAt')
                ->nullable()
                ->property('metadata_expires_at'),
            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('updatedAt'),
        ];
    }

    /**
     * The rules self-registered clients are held to, plus native app schemes,
     * which only an admin may vouch for.
     */
    protected function validateRedirectUris(string $attribute, mixed $value, \Closure $fail): void
    {
        try {
            ClientMetadata::redirectUris($value, allowPrivateUseSchemes: true);
        } catch (ClientMetadataException $e) {
            $fail($e->getMessage());
        }
    }

    /**
     * Null leaves the client unrestricted. An empty list would allow nothing,
     * which only duplicates revoking the client, so it is rejected.
     */
    protected function validateScopes(string $attribute, mixed $value, \Closure $fail): void
    {
        if ($value === []) {
            $fail($this->translator->trans('flarum-oauth-provider.api.scopes_empty_message'));

            return;
        }

        foreach ((array) $value as $scope) {
            if (! is_string($scope) || ! $this->scopes->has($scope)) {
                $fail($this->translator->trans('flarum-oauth-provider.api.scope_unknown_message', [
                    'scope' => is_string($scope) ? $scope : json_encode($scope),
                ]));

                return;
            }
        }
    }

    public function creating(object $model, OriginalContext $context): ?object
    {
        /** @var Client $model */
        $model->id = Str::random(20);
        $model->source = Client::SOURCE_ADMIN;

        $actor = RequestUtil::getActor($context->request);
        $model->created_by = $actor->id;

        if ($model->confidential === null) {
            $model->confidential = true;
        }

        if ($model->confidential) {
            $plainSecret = Str::random(40);
            $model->secret = hash('sha256', $plainSecret);
            $model->plainSecret = $plainSecret;
        }

        $model->created_at = Carbon::now();

        return $model;
    }

    public function updating(object $model, OriginalContext $context): ?object
    {
        /** @var Client $model */
        $model->updated_at = Carbon::now();

        return $model;
    }
}
