<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Http\Controller;

use Defuse\Crypto\Crypto;
use Flarum\OAuthProvider\KeyManager;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Models\RefreshToken;
use Flarum\OAuthProvider\Server\AccessTokenVerifier;
use Flarum\OAuthProvider\Server\Repository\ClientRepository;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * OAuth 2.0 Token Revocation (RFC 7009). Revoking either token of a grant
 * revokes its pair too. Unknown tokens, and tokens of other clients, get the
 * same 200 so the endpoint can't be used to probe tokens.
 */
class RevokeController implements RequestHandlerInterface
{
    public function __construct(
        protected ClientRepository $clients,
        protected AccessTokenVerifier $verifier,
        protected KeyManager $keys,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $params = (array) $request->getParsedBody();
        [$clientId, $secret] = $this->clientCredentials($request, $params);

        if ($clientId === null || ! $this->clients->validateClient($clientId, $secret, null)) {
            return new JsonResponse(
                ['error' => 'invalid_client', 'error_description' => 'Client authentication failed'],
                401,
                ['WWW-Authenticate' => 'Basic realm="OAuth"'],
            );
        }

        $token = $params['token'] ?? null;

        if (! is_string($token) || $token === '') {
            return new JsonResponse(['error' => 'invalid_request', 'error_description' => 'The token parameter is required.'], 400);
        }

        $this->revokeAccessToken($token, $clientId) || $this->revokeRefreshToken($token, $clientId);

        return new EmptyResponse(200);
    }

    protected function revokeAccessToken(string $token, string $clientId): bool
    {
        $tokenId = $this->verifier->verify($token)?->claims()->get('jti');

        if (! is_string($tokenId)) {
            return false;
        }

        $updated = AccessToken::query()->where('id', $tokenId)->where('client_id', $clientId)->update(['revoked' => true]);

        if ($updated > 0) {
            RefreshToken::query()->where('access_token_id', $tokenId)->update(['revoked' => true]);
        }

        return true;
    }

    protected function revokeRefreshToken(string $token, string $clientId): bool
    {
        try {
            $payload = json_decode(Crypto::decrypt($token, $this->keys->encryptionKey()), true);
        } catch (\Throwable) {
            return false;
        }

        if (! is_array($payload) || ($payload['client_id'] ?? null) !== $clientId || ! isset($payload['refresh_token_id'])) {
            return false;
        }

        RefreshToken::query()->where('id', $payload['refresh_token_id'])->update(['revoked' => true]);

        if (isset($payload['access_token_id'])) {
            AccessToken::query()->where('id', $payload['access_token_id'])->where('client_id', $clientId)->update(['revoked' => true]);
        }

        return true;
    }

    /**
     * @param array<mixed> $params
     * @return array{0: string|null, 1: string|null}
     */
    protected function clientCredentials(Request $request, array $params): array
    {
        $header = $request->getHeaderLine('Authorization');

        if (stripos($header, 'Basic ') === 0) {
            $decoded = base64_decode(substr($header, 6), true);

            if ($decoded !== false && str_contains($decoded, ':')) {
                [$id, $secret] = explode(':', $decoded, 2);

                return [urldecode($id), urldecode($secret)];
            }
        }

        $id = $params['client_id'] ?? null;
        $secret = $params['client_secret'] ?? null;

        return [is_string($id) ? $id : null, is_string($secret) ? $secret : null];
    }
}
