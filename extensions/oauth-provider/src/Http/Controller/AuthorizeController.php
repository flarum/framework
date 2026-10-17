<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Http\Controller;

use Carbon\Carbon;
use Flarum\Http\AccessToken;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\OAuthProvider\Models\AuthCode;
use Flarum\OAuthProvider\Models\Consent;
use Flarum\OAuthProvider\Scope\ScopeRegistry;
use Flarum\OAuthProvider\Server\AuthorizationServerFactory;
use Flarum\OAuthProvider\Server\Entity\UserEntity;
use Illuminate\Contracts\View\Factory;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class AuthorizeController implements RequestHandlerInterface
{
    public function __construct(
        protected AuthorizationServerFactory $factory,
        protected Factory $view,
        protected UrlGenerator $url,
        protected ScopeRegistry $scopes,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $server = $this->factory->authorizationServer();

        try {
            $authRequest = $server->validateAuthorizationRequest($request);
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse(new \Laminas\Diactoros\Response());
        }

        $actor = RequestUtil::getActor($request);
        $queryParams = $request->getQueryParams();
        $prompt = $queryParams['prompt'] ?? null;
        $nonce = $queryParams['nonce'] ?? null;
        $maxAge = isset($queryParams['max_age']) ? (int) $queryParams['max_age'] : null;

        $authTime = $this->sessionAuthTime($request);

        // OIDC max_age: if the user's session is older than max_age seconds (or
        // we can't determine when they last authenticated), force re-auth even
        // if they're currently logged in. prompt=login has the same effect.
        $needsReAuth = $actor->isGuest()
            || $prompt === 'login'
            || ($maxAge !== null && ($authTime === null || (Carbon::now()->getTimestamp() - $authTime->getTimestamp()) >= $maxAge));

        if ($needsReAuth) {
            $base = $this->url->to('forum')->base();

            // Build the return-to as a path+query on the configured forum URL so
            // the scheme matches (avoids http/https mismatches behind proxies).
            $uri = $request->getUri();
            $returnPath = $uri->getPath();
            if ($uri->getQuery() !== '') {
                $returnPath .= '?'.$uri->getQuery();
            }
            $returnTo = urlencode($base.$returnPath);

            return new RedirectResponse($base.'/?oauth_login=1&return_to='.$returnTo);
        }

        $clientId = $authRequest->getClient()->getIdentifier();
        $requestedScopes = array_map(fn ($scope) => $scope->getIdentifier(), $authRequest->getScopes());

        $params = $request->getParsedBody() ?? [];
        $method = strtoupper($request->getMethod());

        // The consent form was submitted — record the decision.
        if ($method === 'POST' && isset($params['oauth_consent_approved'])) {
            $approved = $params['oauth_consent_approved'] === '1';

            if ($approved) {
                $this->rememberConsent((int) $actor->id, $clientId, $requestedScopes);
            }

            return $this->completeAuthorization($authRequest, $server, $actor->id, $approved, $nonce, $authTime);
        }

        // Previously-granted consent still covers this request — skip the prompt
        // unless the client explicitly asked for re-consent via prompt=consent.
        if ($prompt !== 'consent' && $this->hasExistingConsent((int) $actor->id, $clientId, $requestedScopes)) {
            return $this->completeAuthorization($authRequest, $server, $actor->id, true, $nonce, $authTime);
        }

        $scopeDescriptions = [];

        foreach ($authRequest->getScopes() as $scope) {
            $scopeDescriptions[$scope->getIdentifier()] = $this->scopes->description($scope->getIdentifier()) ?? $scope->getIdentifier();
        }

        $csrfToken = $request->getAttribute('session')?->token();

        $html = $this->view->make('flarum-oauth-provider::consent', [
            'client' => $authRequest->getClient(),
            'scopes' => $scopeDescriptions,
            'actor' => $actor,
            'formAction' => (string) $request->getUri(),
            'csrfToken' => $csrfToken,
            'queryParams' => $queryParams,
        ])->render();

        return new HtmlResponse($html);
    }

    protected function completeAuthorization(
        AuthorizationRequest $authRequest,
        $server,
        int $userId,
        bool $approved,
        ?string $nonce = null,
        ?Carbon $authTime = null,
    ): ResponseInterface {
        $userEntity = new UserEntity();
        $userEntity->setIdentifier((string) $userId);

        $authRequest->setUser($userEntity);
        $authRequest->setAuthorizationApproved($approved);

        try {
            $response = $server->completeAuthorizationRequest($authRequest, new \Laminas\Diactoros\Response());
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse(new \Laminas\Diactoros\Response());
        }

        // If approved, attach nonce + auth_time to the just-created auth code
        // so they survive through to ID token issuance at the token endpoint.
        if ($approved) {
            AuthCode::query()
                ->where('user_id', $userId)
                ->where('client_id', $authRequest->getClient()->getIdentifier())
                ->orderByDesc('created_at')
                ->limit(1)
                ->update([
                    'nonce' => $nonce,
                    'auth_time' => $authTime ?? Carbon::now(),
                ]);
        }

        return $response;
    }

    /**
     * Best-effort "when did the current session authenticate" timestamp. Uses
     * the session's access token creation time — Flarum reissues a fresh access
     * token each time a remember-me cookie restores a session, so this is the
     * most accurate "this session started" signal we have.
     *
     * Returns null for guests or if the session is orphaned.
     */
    protected function sessionAuthTime(Request $request): ?Carbon
    {
        $session = $request->getAttribute('session');
        $tokenValue = $session?->get('access_token');

        if (! is_string($tokenValue) || $tokenValue === '') {
            return null;
        }

        $token = AccessToken::findValid($tokenValue);

        return $token?->created_at;
    }

    protected function hasExistingConsent(int $userId, string $clientId, array $requestedScopes): bool
    {
        /** @var Consent|null $consent */
        $consent = Consent::query()
            ->where('user_id', $userId)
            ->where('client_id', $clientId)
            ->where('revoked', false)
            ->first();

        return $consent !== null && $consent->covers($requestedScopes);
    }

    protected function rememberConsent(int $userId, string $clientId, array $requestedScopes): void
    {
        /** @var Consent|null $existing */
        $existing = Consent::query()
            ->where('user_id', $userId)
            ->where('client_id', $clientId)
            ->first();

        if ($existing === null) {
            Consent::query()->create([
                'user_id' => $userId,
                'client_id' => $clientId,
                'scopes' => $requestedScopes,
                'revoked' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            return;
        }

        // Merge newly-approved scopes into the existing grant and re-activate it
        // if it was previously revoked.
        $merged = array_values(array_unique(array_merge($existing->scopes ?? [], $requestedScopes)));

        $existing->scopes = $merged;
        $existing->revoked = false;
        $existing->updated_at = Carbon::now();
        $existing->save();
    }
}
