<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\unit;

use Flarum\OAuthProvider\Models\Client;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ClientModelTest extends TestCase
{
    #[Test]
    public function hasRedirectUri_matches_exact_uri(): void
    {
        $client = new Client();
        $client->redirect_uris = ['https://example.com/callback', 'https://other.example.com/cb'];

        $this->assertTrue($client->hasRedirectUri('https://example.com/callback'));
        $this->assertTrue($client->hasRedirectUri('https://other.example.com/cb'));
    }

    #[Test]
    public function hasRedirectUri_rejects_non_matching_uri(): void
    {
        $client = new Client();
        $client->redirect_uris = ['https://example.com/callback'];

        $this->assertFalse($client->hasRedirectUri('https://example.com/'));
        $this->assertFalse($client->hasRedirectUri('https://evil.example.com/callback'));
    }

    #[Test]
    public function secret_is_hidden_from_serialization(): void
    {
        $client = new Client();
        $client->id = 'test';
        $client->name = 'Test';
        $client->secret = 'super-secret';

        $array = $client->toArray();

        $this->assertArrayNotHasKey('secret', $array);
    }
}
