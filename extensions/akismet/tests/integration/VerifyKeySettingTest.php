<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet\Tests\integration;

use Flarum\Akismet\Tests\fixtures\FakeAkismetProvider;
use Flarum\Extend;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;

/**
 * A submitted API key is checked against Akismet's verify-key endpoint before
 * being saved: a typo'd key used to be accepted silently, leaving the forum
 * unprotected with nothing to show for it.
 */
class VerifyKeySettingTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-flags', 'flarum-approval', 'flarum-akismet');

        $this->extend(
            (new Extend\ServiceProvider())->register(FakeAkismetProvider::class)
        );

        FakeAkismetProvider::reset();

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    private function saveKey(?string $key): int
    {
        return $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => ['flarum-akismet.api_key' => $key],
            ])
        )->getStatusCode();
    }

    #[Test]
    public function a_valid_key_saves()
    {
        FakeAkismetProvider::reset([new Response(200, [], 'valid')]);

        $this->assertSame(204, $this->saveKey('a-good-key'));

        // The candidate key, not the configured one, must have been verified.
        parse_str((string) FakeAkismetProvider::$history[0]['request']->getBody(), $params);
        $this->assertSame('a-good-key', $params['api_key']);
        $this->assertStringContainsString('verify-key', (string) FakeAkismetProvider::$history[0]['request']->getUri());
    }

    #[Test]
    public function an_invalid_key_is_rejected_with_a_validation_error()
    {
        FakeAkismetProvider::reset([new Response(200, [], 'invalid')]);

        $this->assertSame(422, $this->saveKey('a-typoed-key'));
    }

    #[Test]
    public function clearing_the_key_is_not_verified()
    {
        FakeAkismetProvider::reset();

        $this->assertSame(204, $this->saveKey(''));
        $this->assertEmpty(FakeAkismetProvider::$history);
    }

    #[Test]
    public function an_unreachable_akismet_does_not_block_saving()
    {
        FakeAkismetProvider::reset([
            new ConnectException('Connection refused', new Request('POST', 'verify-key')),
        ]);

        $this->assertSame(204, $this->saveKey('unverifiable-key'));
    }
}
