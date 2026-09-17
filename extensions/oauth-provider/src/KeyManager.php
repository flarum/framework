<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider;

use Defuse\Crypto\Key;
use Flarum\Settings\SettingsRepositoryInterface;
use League\OAuth2\Server\CryptKey;
use RuntimeException;

/**
 * Manages the RSA keypair used to sign access token JWTs and the symmetric
 * encryption key used for auth codes + refresh tokens.
 *
 * Keys are generated lazily on first use and persisted in the settings store.
 * Call rotate() to regenerate them — existing tokens will be invalidated.
 */
class KeyManager
{
    public const PRIVATE_KEY_SETTING = 'flarum-oauth-provider.private_key';
    public const PUBLIC_KEY_SETTING = 'flarum-oauth-provider.public_key';
    public const ENCRYPTION_KEY_SETTING = 'flarum-oauth-provider.encryption_key';

    public function __construct(protected SettingsRepositoryInterface $settings)
    {
    }

    public function privateKey(): CryptKey
    {
        $key = $this->settings->get(self::PRIVATE_KEY_SETTING);

        if (empty($key)) {
            $this->generateKeyPair();
            $key = $this->settings->get(self::PRIVATE_KEY_SETTING);
        }

        return new CryptKey($key, null, false);
    }

    public function publicKey(): CryptKey
    {
        $key = $this->settings->get(self::PUBLIC_KEY_SETTING);

        if (empty($key)) {
            $this->generateKeyPair();
            $key = $this->settings->get(self::PUBLIC_KEY_SETTING);
        }

        return new CryptKey($key, null, false);
    }

    public function encryptionKey(): Key
    {
        $key = $this->settings->get(self::ENCRYPTION_KEY_SETTING);

        if (empty($key)) {
            $key = Key::createNewRandomKey()->saveToAsciiSafeString();
            $this->settings->set(self::ENCRYPTION_KEY_SETTING, $key);
        }

        return Key::loadFromAsciiSafeString($key);
    }

    public function rotate(): void
    {
        $this->generateKeyPair();
        $this->settings->set(self::ENCRYPTION_KEY_SETTING, Key::createNewRandomKey()->saveToAsciiSafeString());
    }

    private function generateKeyPair(): void
    {
        $resource = \openssl_pkey_new([
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($resource === false) {
            throw new RuntimeException('Failed to generate RSA key pair: '.\openssl_error_string());
        }

        if (! \openssl_pkey_export($resource, $privateKey)) {
            throw new RuntimeException('Failed to export RSA private key: '.\openssl_error_string());
        }

        $details = \openssl_pkey_get_details($resource);

        if ($details === false || ! isset($details['key'])) {
            throw new RuntimeException('Failed to read RSA public key: '.\openssl_error_string());
        }

        $this->settings->set(self::PRIVATE_KEY_SETTING, $privateKey);
        $this->settings->set(self::PUBLIC_KEY_SETTING, $details['key']);
    }
}
