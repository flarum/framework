<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Config\JsonFile;
use Flarum\ExtensionManager\Config\Repositories;
use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use PHPUnit\Framework\TestCase;

/**
 * A credential written into a repository URL never reaches the screen, an
 * error message or a log: not in the user part, the query string or the path.
 */
class RedactTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir().'/mw-redact-'.bin2hex(random_bytes(4)).'.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
    }

    private function file(array $data): JsonFile
    {
        file_put_contents($this->path, json_encode($data));

        return new JsonFile(EnglishTranslator::make(), $this->path);
    }

    /**
     * A token in the query string, or one recognizable in the path, used to
     * reach the screen as written (issue 5357, item 12).
     */
    public function test_a_token_in_the_query_or_the_path_never_reaches_the_screen(): void
    {
        $repos = new Repositories(EnglishTranslator::make(), $this->file(['repositories' => [
            ['type' => 'composer', 'url' => 'https://repo.example.test/composer?token=q-s3cret'],
            ['type' => 'composer', 'url' => 'https://repo.example.test/composer?access_token=a-s3cret&page=2'],
            ['type' => 'composer', 'url' => 'https://repo.example.test/token/p-s3cret/composer'],
            ['type' => 'vcs', 'url' => 'https://example.test/glpat-abcdefghijklmnopqrst/repo.git'],
        ]]));

        $shown = (string) json_encode($repos->all());

        foreach (['q-s3cret', 'a-s3cret', 'p-s3cret', 'glpat-'] as $secret) {
            $this->assertStringNotContainsString($secret, $shown);
        }

        $this->assertSame([
            'https://repo.example.test/composer?token=***',
            'https://repo.example.test/composer?access_token=***&page=***',
            'https://repo.example.test/token/***/composer',
            'https://example.test/***/repo.git',
        ], array_column($repos->all(), 'url'));
    }

    public function test_redacting_leaves_a_url_with_nothing_secret_in_it_alone(): void
    {
        foreach ([
            'https://repo.example.test/composer',
            'https://api.github.com/repos/acme/widget/zipball/0123456789abcdef0123456789abcdef01234567',
            'https://github.com/acme/token-manager.git',
            'https://example.test/keys/',
            'git@github.com:a/b.git',
            'not a url',
        ] as $url) {
            $this->assertSame($url, Repositories::redact($url));
        }

        $this->assertSame('https://example.test/x?***', Repositories::redact('https://example.test/x?ghp_abcdefghijklmnop'), 'a bare query value too');
        $this->assertSame('https://***@example.test/x?a=***#frag', Repositories::redact('https://tok@example.test/x?a=1#frag'));
    }

    /** For an error message or a log line, every URL in it, wherever it is. */
    public function test_every_url_in_a_message_is_redacted(): void
    {
        $message = 'Could not download https://repo.example.test/dist/a.zip?token=s3cret (HTTP 404). '
            .'cURL error 28: Operation timed out for https://user:pass@repo.example.test/b.zip';

        $this->assertSame(
            'Could not download https://repo.example.test/dist/a.zip?token=*** (HTTP 404). '
            .'cURL error 28: Operation timed out for https://***@repo.example.test/b.zip',
            Repositories::redactUrls($message)
        );
        $this->assertSame('Nothing to see here.', Repositories::redactUrls('Nothing to see here.'));
    }
}
