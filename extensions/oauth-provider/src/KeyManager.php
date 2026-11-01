<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider;

use Defuse\Crypto\Key;
use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use League\OAuth2\Server\CryptKey;
use RuntimeException;

/**
 * Manages the RSA private key used to sign access token JWTs and ID tokens,
 * and the symmetric encryption key used for auth codes + refresh tokens.
 *
 * The keys are files in `storage/oauth-provider`, readable by the owner only.
 * They are never settings: every setting is sent to admins' browsers. Keys
 * are generated on first use; when concurrent first requests race, the first
 * key written wins and the others use it. Keys from older installs, kept in
 * settings, are moved to the files on first use.
 *
 * Call rotate() to regenerate them — existing tokens will be invalidated.
 */
class KeyManager
{
    public const string PRIVATE_KEY_FILE = 'private.key';
    public const string ENCRYPTION_KEY_FILE = 'encryption.key';

    /** Settings the keys used to be kept in. */
    public const string PRIVATE_KEY_SETTING = 'flarum-oauth-provider.private_key';
    public const string PUBLIC_KEY_SETTING = 'flarum-oauth-provider.public_key';
    public const string ENCRYPTION_KEY_SETTING = 'flarum-oauth-provider.encryption_key';

    public function __construct(
        protected Paths $paths,
        protected SettingsRepositoryInterface $settings,
    ) {
    }

    public function privateKey(): CryptKey
    {
        return new CryptKey($this->privateKeyPem(), null, false);
    }

    /**
     * Derived from the private key, so the two always belong together.
     */
    public function publicKey(): CryptKey
    {
        $key = \openssl_pkey_get_private($this->privateKeyPem());
        $details = $key === false ? false : \openssl_pkey_get_details($key);

        if ($details === false || ! isset($details['key'])) {
            throw new RuntimeException('Failed to read the RSA public key: '.\openssl_error_string());
        }

        return new CryptKey($details['key'], null, false);
    }

    public function encryptionKey(): Key
    {
        return Key::loadFromAsciiSafeString($this->load(
            self::ENCRYPTION_KEY_FILE,
            [self::ENCRYPTION_KEY_SETTING],
            fn () => Key::createNewRandomKey()->saveToAsciiSafeString(),
        ));
    }

    public function rotate(): void
    {
        $this->write(self::PRIVATE_KEY_FILE, $this->generatePrivateKey(), replace: true);
        $this->write(self::ENCRYPTION_KEY_FILE, Key::createNewRandomKey()->saveToAsciiSafeString(), replace: true);
    }

    protected function privateKeyPem(): string
    {
        return $this->load(
            self::PRIVATE_KEY_FILE,
            [self::PRIVATE_KEY_SETTING, self::PUBLIC_KEY_SETTING],
            fn () => $this->generatePrivateKey(),
        );
    }

    /**
     * The key in the given file, creating the file on first use from the
     * first legacy setting or, failing that, a newly generated key.
     *
     * @param list<string> $legacySettings
     * @param callable(): string $generate
     */
    protected function load(string $file, array $legacySettings, callable $generate): string
    {
        $key = $this->read($file);

        if ($key !== null) {
            return $key;
        }

        $legacy = $this->settings->get($legacySettings[0]);
        $key = $this->write($file, is_string($legacy) && $legacy !== '' ? $legacy : $generate());

        foreach ($legacySettings as $setting) {
            $this->settings->delete($setting);
        }

        return $key;
    }

    protected function directory(): string
    {
        return $this->paths->storage.'/oauth-provider';
    }

    protected function read(string $file): ?string
    {
        $path = $this->directory().'/'.$file;
        $contents = is_file($path) ? file_get_contents($path) : false;

        return $contents === false || $contents === '' ? null : $contents;
    }

    /**
     * Writes a complete key file in one step, readable by the owner only, and
     * returns the key now stored. Unless replacing, an existing file is kept:
     * a request that loses a race uses the key that won.
     */
    protected function write(string $file, string $key, bool $replace = false): string
    {
        $directory = $this->directory();

        if (! is_dir($directory) && ! @mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException("Could not create the key directory $directory.");
        }

        $path = $directory.'/'.$file;
        $temporary = tempnam($directory, '.'.$file.'.');

        if ($temporary === false) {
            throw new RuntimeException("Could not write to the key directory $directory.");
        }

        try {
            if (! chmod($temporary, 0600) || file_put_contents($temporary, $key) !== strlen($key)) {
                throw new RuntimeException("Could not write the key file $path.");
            }

            if ($replace) {
                rename($temporary, $path);
            } elseif (! @link($temporary, $path) && ! file_exists($path)) {
                // link() never replaces a file. Without hard link support,
                // fall back to a rename, which still never exposes half a key.
                rename($temporary, $path);
            }
        } finally {
            if (file_exists($temporary)) {
                unlink($temporary);
            }
        }

        return $this->read($file) ?? throw new RuntimeException("Could not read the key file $path.");
    }

    protected function generatePrivateKey(): string
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

        return $privateKey;
    }
}
