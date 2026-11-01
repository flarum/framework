<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Http\Controller;

use Flarum\OAuthProvider\KeyManager;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * JSON Web Key Set endpoint — `/.well-known/jwks.json`.
 *
 * Exposes the RSA public key so OIDC/OAuth 2 clients can verify JWT access
 * tokens and ID tokens issued by this provider without a pre-shared key.
 */
class JwksController implements RequestHandlerInterface
{
    public function __construct(protected KeyManager $keys)
    {
    }

    public function handle(Request $request): ResponseInterface
    {
        $publicKeyPem = $this->keys->publicKey()->getKeyContents();

        $details = openssl_pkey_get_details(openssl_pkey_get_public($publicKeyPem) ?: throw new RuntimeException('Invalid public key'));

        if ($details === false || ! isset($details['rsa'])) {
            throw new RuntimeException('Failed to extract RSA key details');
        }

        $modulus = $details['rsa']['n'];
        $exponent = $details['rsa']['e'];

        // Key ID: a deterministic fingerprint so clients can cache by kid.
        $kid = substr(hash('sha256', $publicKeyPem), 0, 16);

        return new JsonResponse([
            'keys' => [[
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => 'RS256',
                'kid' => $kid,
                'n' => $this->base64UrlEncode($modulus),
                'e' => $this->base64UrlEncode($exponent),
            ]],
        ]);
    }

    private function base64UrlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
