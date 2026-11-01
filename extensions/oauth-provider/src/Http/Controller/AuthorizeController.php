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
use Flarum\OAuthProvider\ClientRegistration\ClientMetadataException;
use Flarum\OAuthProvider\ClientRegistration\MetadataDocumentClients;
use Flarum\OAuthProvider\Models\Client;
use Flarum\OAuthProvider\Models\Consent;
use Flarum\OAuthProvider\Scope\ScopeRegistry;
use Flarum\OAuthProvider\Server\AuthorizationServerFactory;
use Flarum\OAuthProvider\Server\AuthorizationServerMetadata;
use Flarum\OAuthProvider\Server\Entity\UserEntity;
use Flarum\OAuthProvider\Server\RequestType\AuthorizationRequest as ResourceAuthorizationRequest;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\View\Factory;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class AuthorizeController implements RequestHandlerInterface
{
    /** Session key: re-authentication tickets mapped to the login they were issued under. */
    protected const string REAUTH_TICKETS = 'flarum-oauth-provider.reauth_tickets';

    /** Query parameter carrying a request's re-authentication ticket back from the login form. */
    public const string REAUTH_TICKET_PARAM = 'oauth_reauth_ticket';

    /** Seconds a re-authentication ticket stays valid. */
    protected const int REAUTH_TICKET_LIFETIME = 600;

    public function __construct(
        protected AuthorizationServerFactory $factory,
        protected Factory $view,
        protected UrlGenerator $url,
        protected ScopeRegistry $scopes,
        protected MetadataDocumentClients $metadataDocuments,
        protected AuthorizationServerMetadata $metadata,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        // The consent screen must never be framed (clickjacking); the headers
        // go on every response so no branch can forget them.
        return $this->authorize($request)
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Content-Security-Policy', "frame-ancestors 'none'");
    }

    protected function authorize(Request $request): ResponseInterface
    {
        $clientId = $request->getQueryParams()['client_id'] ?? null;

        if (is_string($clientId)) {
            try {
                $this->metadataDocuments->resolve($clientId, (string) $request->getAttribute('ipAddress'));
            } catch (ClientMetadataException $e) {
                return new JsonResponse(['error' => $e->error, 'error_description' => $e->getMessage()], 400);
            }
        }

        $server = $this->factory->authorizationServer();

        try {
            $authRequest = $server->validateAuthorizationRequest($request);
        } catch (OAuthServerException $exception) {
            return $this->withIssuer($exception->generateHttpResponse(new \Laminas\Diactoros\Response()));
        }

        $actor = RequestUtil::getActor($request);
        $queryParams = $request->getQueryParams();
        $prompt = $queryParams['prompt'] ?? null;
        $nonce = $queryParams['nonce'] ?? null;
        $maxAge = isset($queryParams['max_age']) ? (int) $queryParams['max_age'] : null;
        $prompts = is_string($prompt) ? preg_split('/\s+/', trim($prompt), -1, PREG_SPLIT_NO_EMPTY) : [];
        $promptNone = in_array('none', $prompts, true);

        $authTime = $this->sessionAuthTime($request);

        // OIDC max_age: if the user's session is older than max_age seconds (or
        // we can't determine when they last authenticated), force re-auth even
        // if they're currently logged in. prompt=login has the same effect.
        /** @var Session|null $session */
        $session = $request->getAttribute('session');
        $ticket = $queryParams[self::REAUTH_TICKET_PARAM] ?? null;
        $ticket = is_string($ticket) ? $ticket : null;

        $forcesReAuth = in_array('login', $prompts, true)
            || ($maxAge !== null && ($authTime === null || (Carbon::now()->getTimestamp() - $authTime->getTimestamp()) >= $maxAge));
        $needsReAuth = $actor->isGuest() || $forcesReAuth;

        // Re-authentication this very request asked for is satisfied once the
        // user has signed in again; without this, prompt=login and max_age=0
        // would ask forever.
        if ($needsReAuth && ! $actor->isGuest() && $this->reAuthSatisfied($session, $ticket)) {
            $needsReAuth = false;
        }

        if ($needsReAuth) {
            if ($promptNone) {
                return $this->authorizationError($authRequest, 'login_required', 'The user needs to authenticate.');
            }

            $base = $this->url->to('forum')->base();

            // Build the return-to as a path+query on the configured forum URL so
            // the scheme matches (avoids http/https mismatches behind proxies).
            $uri = $request->getUri();
            $returnPath = $uri->getPath();
            $returnQuery = $uri->getQuery();

            // A request that demands a fresh login returns with a ticket tying
            // the next login to it, so it neither satisfies nor is satisfied by
            // another. A guest has no login yet, so any login satisfies theirs.
            if ($forcesReAuth && $session !== null) {
                $returnQuery = $this->withQueryParam($returnQuery, self::REAUTH_TICKET_PARAM, $this->reAuthTicket($session, $ticket));
            } elseif (! $actor->isGuest()) {
                return $this->authorizationError($authRequest, 'login_required', 'The user needs to authenticate.');
            }

            if ($returnQuery !== '') {
                $returnPath .= '?'.$returnQuery;
            }
            $returnTo = urlencode($base.$returnPath);

            // A signed-in user must be shown the login form anyway.
            $reAuth = $actor->isGuest() ? '' : '&oauth_reauth=1';

            return new RedirectResponse($base.'/?oauth_login=1'.$reAuth.'&return_to='.$returnTo);
        }

        $clientId = $authRequest->getClient()->getIdentifier();
        $redirectUris = array_values((array) $authRequest->getClient()->getRedirectUri());
        $requestedScopes = array_map(fn ($scope) => $scope->getIdentifier(), $authRequest->getScopes());

        $params = $request->getParsedBody() ?? [];
        $method = strtoupper($request->getMethod());

        // The consent form was submitted — record the decision.
        if ($method === 'POST' && isset($params['oauth_consent_approved'])) {
            $approved = $params['oauth_consent_approved'] === '1';

            if ($approved) {
                $this->rememberConsent((int) $actor->id, $clientId, $requestedScopes, $redirectUris);
            }

            $this->forgetReAuthTicket($session, $ticket);

            return $this->completeAuthorization($authRequest, $server, $actor->id, $approved, $nonce, $authTime);
        }

        // Previously-granted consent still covers this request — skip the prompt
        // unless the client explicitly asked for re-consent via prompt=consent.
        if (! in_array('consent', $prompts, true) && $this->hasExistingConsent((int) $actor->id, $clientId, $requestedScopes, $redirectUris)) {
            $this->forgetReAuthTicket($session, $ticket);

            return $this->completeAuthorization($authRequest, $server, $actor->id, true, $nonce, $authTime);
        }

        if ($promptNone) {
            $this->forgetReAuthTicket($session, $ticket);

            return $this->authorizationError($authRequest, 'interaction_required', 'The user needs to grant consent.');
        }

        $scopeDescriptions = [];

        foreach ($authRequest->getScopes() as $scope) {
            $scopeDescriptions[$scope->getIdentifier()] = $this->scopes->description($scope->getIdentifier()) ?? $scope->getIdentifier();
        }

        $csrfToken = $request->getAttribute('session')?->token();

        /** @var Client|null $clientModel */
        $clientModel = Client::query()->find($clientId);

        // Self-described clients (metadata document, dynamic registration)
        // choose their own name, so they are marked unverified and the user
        // is shown where they would be sent back to.
        $selfDescribed = $clientModel !== null && $clientModel->source !== Client::SOURCE_ADMIN;
        $redirectHost = $selfDescribed ? $this->redirectHost($authRequest) : null;

        // Only a host the server has checked names the client: the metadata
        // document's own URL. A self-registered client's client_uri is its own
        // claim, so it is named only by the redirect host shown below.
        $sourceHost = $clientModel?->source === Client::SOURCE_METADATA_DOCUMENT
            ? $clientModel->sourceHost()
            : null;

        $html = $this->view->make('flarum-oauth-provider::consent', [
            'client' => $authRequest->getClient(),
            'unverified' => $selfDescribed,
            'sourceHost' => $sourceHost,
            'redirectHost' => $redirectHost,
            'scopes' => $scopeDescriptions,
            'actor' => $actor,
            'formAction' => (string) $request->getUri(),
            'csrfToken' => $csrfToken,
            // Replayed as hidden fields; only plain string parameters belong
            // to an authorization request.
            'queryParams' => array_filter($queryParams, is_string(...)),
        ])->render();

        return new HtmlResponse($html);
    }

    protected function completeAuthorization(
        AuthorizationRequest $authRequest,
        AuthorizationServer $server,
        int $userId,
        bool $approved,
        ?string $nonce = null,
        ?Carbon $authTime = null,
    ): ResponseInterface {
        $userEntity = new UserEntity();
        $userEntity->setIdentifier((string) $userId);

        $authRequest->setUser($userEntity);
        $authRequest->setAuthorizationApproved($approved);

        if ($authRequest instanceof ResourceAuthorizationRequest) {
            $authRequest->setNonce($nonce);
            $authRequest->setAuthTime(($authTime ?? Carbon::now())->toDateTimeImmutable());
        }

        try {
            $response = $this->withIssuer($server->completeAuthorizationRequest($authRequest, new \Laminas\Diactoros\Response()));
        } catch (OAuthServerException $exception) {
            return $this->withIssuer($exception->generateHttpResponse(new \Laminas\Diactoros\Response()));
        }

        return $response;
    }

    protected function redirectHost(AuthorizationRequest $authRequest): ?string
    {
        $redirectUri = $authRequest->getRedirectUri() ?? (array) $authRequest->getClient()->getRedirectUri();
        $redirectUri = is_array($redirectUri) ? (string) reset($redirectUri) : $redirectUri;

        return Client::hostOf($redirectUri);
    }

    /**
     * Returns an OIDC interaction error to a previously validated redirect URI.
     */
    protected function authorizationError(AuthorizationRequest $authRequest, string $error, string $description): ResponseInterface
    {
        $redirectUri = $authRequest->getRedirectUri() ?? (array) $authRequest->getClient()->getRedirectUri();
        $redirectUri = is_array($redirectUri) ? (string) reset($redirectUri) : $redirectUri;
        $params = ['error' => $error, 'error_description' => $description];

        if ($authRequest->getState() !== null) {
            $params['state'] = $authRequest->getState();
        }

        return $this->withIssuer(new RedirectResponse($redirectUri.(str_contains($redirectUri, '?') ? '&' : '?').http_build_query($params)));
    }

    /**
     * Adds the RFC 9207 `iss` parameter to a redirect back to the client.
     */
    protected function withIssuer(ResponseInterface $response): ResponseInterface
    {
        $location = $response->getHeaderLine('Location');

        if ($location === '') {
            return $response;
        }

        $separator = str_contains($location, '?') ? '&' : '?';

        return $response->withHeader('Location', $location.$separator.http_build_query(['iss' => $this->metadata->issuer()]));
    }

    /**
     * When the current session's user authenticated: the creation time of the
     * session's access token. Restoring a session from a remember-me cookie
     * reuses the remember token the user got when they signed in, so this is
     * the time of that sign-in (OIDC auth_time), not of the restore.
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

    /**
     * The ticket for a re-authentication request, reusing the request's own
     * while it is still pending. Only the last few are kept per session.
     */
    protected function reAuthTicket(Session $session, ?string $ticket): string
    {
        $tickets = (array) $session->get(self::REAUTH_TICKETS, []);

        if ($ticket === null || ! $this->ticketIsCurrent($tickets[$ticket] ?? null)) {
            $ticket = bin2hex(random_bytes(16));
            $tickets[$ticket] = ['login' => $this->loginFingerprint($session), 'at' => Carbon::now()->getTimestamp()];
        }

        $session->put(self::REAUTH_TICKETS, array_slice($tickets, -10, null, true));

        return $ticket;
    }

    /**
     * Whether the session has signed in with a different login token since the
     * ticket was issued. A new login, not a later timestamp: logins can share a second.
     */
    protected function reAuthSatisfied(?Session $session, ?string $ticket): bool
    {
        $entry = $ticket === null ? null : ($session?->get(self::REAUTH_TICKETS, [])[$ticket] ?? null);

        return $this->ticketIsCurrent($entry) && ! hash_equals($entry['login'], $this->loginFingerprint($session));
    }

    /**
     * @phpstan-assert-if-true array{login: string, at: int} $entry
     */
    protected function ticketIsCurrent(mixed $entry): bool
    {
        return is_array($entry)
            && is_string($entry['login'] ?? null)
            && is_int($entry['at'] ?? null)
            && Carbon::now()->getTimestamp() - $entry['at'] <= self::REAUTH_TICKET_LIFETIME;
    }

    /**
     * Sets one parameter on a raw query string, leaving the rest as it was sent.
     */
    protected function withQueryParam(string $query, string $name, string $value): string
    {
        $pairs = array_filter(
            $query === '' ? [] : explode('&', $query),
            fn (string $pair) => urldecode(explode('=', $pair, 2)[0]) !== $name
        );

        $pairs[] = rawurlencode($name).'='.rawurlencode($value);

        return implode('&', $pairs);
    }

    protected function forgetReAuthTicket(?Session $session, ?string $ticket): void
    {
        if ($session === null || $ticket === null) {
            return;
        }

        $tickets = (array) $session->get(self::REAUTH_TICKETS, []);
        unset($tickets[$ticket]);
        $session->put(self::REAUTH_TICKETS, $tickets);
    }

    protected function loginFingerprint(?Session $session): string
    {
        $token = $session?->get('access_token');

        return is_string($token) && $token !== '' ? hash('sha256', $token) : '';
    }

    /**
     * @param list<string> $redirectUris The client's current redirect URIs.
     */
    protected function hasExistingConsent(int $userId, string $clientId, array $requestedScopes, array $redirectUris): bool
    {
        /** @var Consent|null $consent */
        $consent = Consent::query()
            ->where('user_id', $userId)
            ->where('client_id', $clientId)
            ->where('revoked', false)
            ->first();

        // A client that changed where it sends codes (a refetched metadata
        // document, say) has to ask again.
        return $consent !== null && $consent->covers($requestedScopes) && $consent->coversRedirectUris($redirectUris);
    }

    /**
     * @param list<string> $redirectUris The client's current redirect URIs.
     */
    protected function rememberConsent(int $userId, string $clientId, array $requestedScopes, array $redirectUris): void
    {
        $redirectUrisHash = Consent::hashRedirectUris($redirectUris);

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
                'redirect_uris_hash' => $redirectUrisHash,
                'revoked' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            return;
        }

        // Merge newly-approved scopes into the existing grant and re-activate it
        // if it was previously revoked. Scopes approved while the client had
        // other redirect URIs are dropped.
        $previous = $existing->coversRedirectUris($redirectUris) ? ($existing->scopes ?? []) : [];

        $existing->scopes = array_values(array_unique(array_merge($previous, $requestedScopes)));
        $existing->redirect_uris_hash = $redirectUrisHash;
        $existing->revoked = false;
        $existing->updated_at = Carbon::now();
        $existing->save();
    }
}
