<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Flarum\OAuthProvider\KeyManager;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use Psr\Clock\ClockInterface;

/**
 * Parses an access token JWT issued by this provider and checks its signature
 * plus any extra constraints. Never throws for bad input.
 */
class AccessTokenVerifier
{
    public function __construct(protected KeyManager $keys)
    {
    }

    public function verify(#[\SensitiveParameter] string $jwt, Constraint ...$constraints): ?UnencryptedToken
    {
        if ($jwt === '') {
            return null;
        }

        try {
            $token = (new Parser(new JoseEncoder()))->parse($jwt);
        } catch (\Throwable) {
            return null;
        }

        if (! $token instanceof UnencryptedToken) {
            return null;
        }

        $signedWith = new SignedWith(new Sha256(), InMemory::plainText($this->keys->publicKey()->getKeyContents()));

        return (new Validator())->validate($token, $signedWith, ...$constraints) ? $token : null;
    }

    /**
     * Carbon-backed so Carbon::setTestNow() applies.
     */
    public function clock(): ClockInterface
    {
        return new class implements ClockInterface {
            public function now(): DateTimeImmutable
            {
                return CarbonImmutable::now();
            }
        };
    }
}
