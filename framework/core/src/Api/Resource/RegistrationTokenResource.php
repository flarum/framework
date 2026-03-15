<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Flarum\Api\Endpoint;
use Flarum\Api\Schema;
use Flarum\User\Exception\InvalidConfirmationTokenException;
use Flarum\User\RegistrationToken;
use Tobyz\JsonApiServer\Context as BaseContext;
use Tobyz\JsonApiServer\Resource\Findable;

/**
 * Exposes the non-sensitive fields of a RegistrationToken so that a redirect-
 * based OAuth flow can pre-populate the SignUpModal after bouncing back to the
 * forum.  The token string itself acts as the credential — no authentication
 * is required because possession of the (random, short-lived) token is proof
 * of authorisation.
 *
 * Only username, email, and the list of pre-filled field names are exposed.
 * The provider name, identifier, and payload are NOT exposed.
 *
 * @extends AbstractResource<RegistrationToken>
 */
class RegistrationTokenResource extends AbstractResource implements Findable
{
    public function type(): string
    {
        return 'registration-tokens';
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make('registration-token.show'),
        ];
    }

    public function find(string $id, BaseContext $context): ?object
    {
        try {
            return RegistrationToken::validOrFail($id);
        } catch (InvalidConfirmationTokenException) {
            return null;
        }
    }

    public function getId(object $model, BaseContext $context): string
    {
        /** @var RegistrationToken $model */
        return $model->token;
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('username')
                ->get(fn (RegistrationToken $token) => $token->user_attributes['username']
                    ?? ($token->payload['suggested']['username'] ?? null)),
            Schema\Str::make('email')
                ->get(fn (RegistrationToken $token) => $token->user_attributes['email']
                    ?? ($token->payload['suggested']['email'] ?? null)),
            Schema\Arr::make('provided')
                ->get(fn (RegistrationToken $token) => array_keys($token->user_attributes ?? [])),
        ];
    }
}
