<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Deck\Tests\integration;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class AccessTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-deck');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    protected function revokeDeckPermission(): void
    {
        $this->database()->table('group_permission')->where('permission', 'deck.use')->delete();
    }

    public function test_guests_cannot_open_the_deck(): void
    {
        $response = $this->send($this->request('GET', '/deck'));

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_members_can_open_the_deck_by_default(): void
    {
        $response = $this->send($this->request('GET', '/deck', ['authenticatedAs' => 2]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_members_without_permission_cannot_open_the_deck(): void
    {
        $this->revokeDeckPermission();

        $response = $this->send($this->request('GET', '/deck', ['authenticatedAs' => 2]));

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_admins_can_always_open_the_deck(): void
    {
        $this->revokeDeckPermission();

        $response = $this->send($this->request('GET', '/deck', ['authenticatedAs' => 1]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_forum_payload_tells_members_they_can_use_the_deck(): void
    {
        $attributes = $this->forumAttributes(2);

        $this->assertTrue($attributes['canUseDeck']);
        $this->assertSame(8, $attributes['deckMaxColumns']);
        $this->assertSame(60, $attributes['deckPollInterval']);
    }

    public function test_forum_payload_reveals_nothing_else_without_permission(): void
    {
        $this->revokeDeckPermission();

        $attributes = $this->forumAttributes(2);

        $this->assertFalse($attributes['canUseDeck']);
        $this->assertArrayNotHasKey('deckMaxColumns', $attributes);
        $this->assertArrayNotHasKey('deckPollInterval', $attributes);
    }

    public function test_forum_payload_reveals_nothing_else_to_guests(): void
    {
        $attributes = $this->forumAttributes(null);

        $this->assertFalse($attributes['canUseDeck']);
        $this->assertArrayNotHasKey('deckMaxColumns', $attributes);
    }

    protected function forumAttributes(?int $userId): array
    {
        $response = $this->send($this->request('GET', '/api', $userId ? ['authenticatedAs' => $userId] : []));

        $this->assertEquals(200, $response->getStatusCode());

        return json_decode($response->getBody()->getContents(), true)['data']['attributes'];
    }
}
