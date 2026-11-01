<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration;

use Defuse\Crypto\Key;
use Flarum\Foundation\Paths;
use Flarum\OAuthProvider\KeyManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class KeyStorageTest extends TestCase
{
    /** @var list<string> */
    protected array $keyDirectories = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-oauth-provider');
    }

    /**
     * @return array<string, mixed>
     */
    protected function adminPayload(): array
    {
        $response = $this->send($this->request('GET', '/admin', ['authenticatedAs' => 1]));

        preg_match('/<script id="flarum-json-payload" type="application\/json">(.+?)<\/script>/s', (string) $response->getBody(), $matches);
        $this->assertNotEmpty($matches, 'The admin page carried no JSON payload.');

        return json_decode(html_entity_decode($matches[1]), true);
    }

    #[Test]
    public function signing_and_encryption_keys_never_reach_the_admin_frontend(): void
    {
        // First use generates the keys.
        $this->assertSame(200, $this->send($this->request('GET', '/.well-known/jwks.json'))->getStatusCode());

        $keys = $this->app()->getContainer()->make(KeyManager::class);
        $privateKey = $keys->privateKey()->getKeyContents();
        $encryptionKey = $keys->encryptionKey()->saveToAsciiSafeString();

        $settings = json_encode($this->adminPayload()['settings']);

        $this->assertStringNotContainsString('PRIVATE KEY', $settings, 'The signing key must not be sent to admins');
        $this->assertStringNotContainsString($encryptionKey, $settings, 'The encryption key must not be sent to admins');
        $this->assertNotEmpty($privateKey);
    }

    #[Test]
    public function concurrent_first_use_ends_with_one_keypair(): void
    {
        $directory = $this->keyDirectory();

        // A looks for keys and finds none; B creates them; A carries on.
        $b = $this->keyManager($directory);
        $a = $this->keyManager($directory, staleFirstRead: true);

        $bPrivate = $b->privateKey()->getKeyContents();
        $bEncryption = $b->encryptionKey()->saveToAsciiSafeString();
        $aPrivate = $a->privateKey()->getKeyContents();
        $aEncryption = $a->encryptionKey()->saveToAsciiSafeString();

        $this->assertSame($bPrivate, $aPrivate, 'Both requests must end up signing with one key');
        $this->assertSame($bEncryption, $aEncryption, 'Both requests must end up with one encryption key');
        $this->assertSame($b->publicKey()->getKeyContents(), $a->publicKey()->getKeyContents());
        $this->assertSame($bPrivate, file_get_contents($directory.'/'.KeyManager::PRIVATE_KEY_FILE));
    }

    #[Test]
    public function key_files_are_readable_by_their_owner_only(): void
    {
        $directory = $this->keyDirectory();
        $keys = $this->keyManager($directory);

        $keys->privateKey();
        $keys->encryptionKey();

        $this->assertSame(0700, fileperms($directory) & 0777);
        $this->assertSame(0600, fileperms($directory.'/'.KeyManager::PRIVATE_KEY_FILE) & 0777);
        $this->assertSame(0600, fileperms($directory.'/'.KeyManager::ENCRYPTION_KEY_FILE) & 0777);
        $this->assertSame(['.lock', KeyManager::ENCRYPTION_KEY_FILE, KeyManager::PRIVATE_KEY_FILE], array_values(array_diff(scandir($directory), ['.', '..'])), 'No temporary files are left behind');
    }

    #[Test]
    public function an_empty_key_file_is_replaced(): void
    {
        $directory = $this->keyDirectory();
        mkdir($directory, 0700);
        touch($directory.'/'.KeyManager::PRIVATE_KEY_FILE);

        $key = $this->keyManager($directory)->privateKey()->getKeyContents();

        $this->assertStringContainsString('PRIVATE KEY', $key);
        $this->assertSame($key, file_get_contents($directory.'/'.KeyManager::PRIVATE_KEY_FILE));
    }

    #[Test]
    public function keys_kept_in_settings_move_to_files(): void
    {
        $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $this->assertNotFalse($resource);
        openssl_pkey_export($resource, $privateKey);
        $encryptionKey = Key::createNewRandomKey()->saveToAsciiSafeString();

        $settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);
        $settings->set(KeyManager::PRIVATE_KEY_SETTING, $privateKey);
        $settings->set(KeyManager::PUBLIC_KEY_SETTING, openssl_pkey_get_details($resource)['key']);
        $settings->set(KeyManager::ENCRYPTION_KEY_SETTING, $encryptionKey);

        $keys = $this->keyManager($this->keyDirectory());

        $this->assertSame($privateKey, $keys->privateKey()->getKeyContents(), 'The existing signing key is kept');
        $this->assertSame($encryptionKey, $keys->encryptionKey()->saveToAsciiSafeString(), 'The existing encryption key is kept');
        $this->assertSame(0, $this->database()->table('settings')->where('key', 'like', 'flarum-oauth-provider.%_key')->count(), 'The keys are gone from the settings');
    }

    #[Test]
    public function a_key_left_in_the_settings_is_removed_once_the_file_exists(): void
    {
        $directory = $this->keyDirectory();
        $this->keyManager($directory)->privateKey();

        // A request stored the file but died before clearing the setting.
        $settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);
        $settings->set(KeyManager::PRIVATE_KEY_SETTING, file_get_contents($directory.'/'.KeyManager::PRIVATE_KEY_FILE));

        $this->keyManager($directory)->privateKey();

        $this->assertSame(0, $this->database()->table('settings')->where('key', KeyManager::PRIVATE_KEY_SETTING)->count(), 'The key must not stay in the settings');
    }

    protected function keyDirectory(): string
    {
        return $this->keyDirectories[] = sys_get_temp_dir().'/oauth-provider-keys-'.bin2hex(random_bytes(6));
    }

    protected function keyManager(string $directory, bool $staleFirstRead = false): KeyManager
    {
        $container = $this->app()->getContainer();

        return new class($container->make(Paths::class), $container->make(SettingsRepositoryInterface::class), $directory, $staleFirstRead) extends KeyManager {
            /** @var array<string, bool> */
            protected array $missed = [];

            public function __construct(Paths $paths, SettingsRepositoryInterface $settings, protected string $keyDirectory, protected bool $staleFirstRead)
            {
                parent::__construct($paths, $settings);
            }

            protected function directory(): string
            {
                return $this->keyDirectory;
            }

            protected function read(string $file): ?string
            {
                if ($this->staleFirstRead && ! isset($this->missed[$file])) {
                    $this->missed[$file] = true;

                    return null;
                }

                return parent::read($file);
            }
        };
    }

    protected function tearDown(): void
    {
        foreach ($this->keyDirectories as $directory) {
            array_map('unlink', glob($directory.'/{,.}*[!.]*', GLOB_BRACE) ?: []);
            @rmdir($directory);
        }

        parent::tearDown();
    }
}
