<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class OpenSearchSuggestionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Lightsail rigging guide', 'slug' => 'lightsail-rigging-guide', 'user_id' => 1, 'last_posted_at' => '2026-10-01 10:00:00'],
                ['id' => 2, 'title' => 'Lightsail tuning', 'slug' => 'lightsail-tuning', 'user_id' => 1, 'last_posted_at' => '2026-10-02 10:00:00'],
                ['id' => 3, 'title' => 'Something else', 'slug' => 'something-else', 'user_id' => 1],
                ['id' => 4, 'title' => 'Lightsail secrets', 'slug' => 'lightsail-secrets', 'user_id' => 1, 'is_private' => true],
                ['id' => 5, 'title' => 'Boat insurance claims', 'slug' => 'boat-insurance-claims', 'user_id' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'content' => '<t><p>How to rig it.</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'user_id' => 1, 'content' => '<t><p>How to tune it.</p></t>'],
                ['id' => 3, 'discussion_id' => 3, 'user_id' => 1, 'content' => '<t><p>Nothing to see.</p></t>'],
                ['id' => 4, 'discussion_id' => 4, 'user_id' => 1, 'content' => '<t><p>Private.</p></t>'],
                ['id' => 5, 'discussion_id' => 5, 'user_id' => 1, 'content' => '<t><p>Who covers what.</p></t>'],
            ],
        ]);
    }

    private function suggest(string $q): array
    {
        $response = $this->send($this->request('GET', '/api/opensearch/suggestions')->withQueryParams(['q' => $q]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('application/x-suggestions+json', $response->getHeaderLine('Content-Type'));

        return json_decode((string) $response->getBody(), true);
    }

    #[Test]
    public function matching_discussions_are_suggested_with_their_addresses(): void
    {
        [$query, $titles, $descriptions, $urls] = $this->suggest('lightsail');

        $suggestions = array_combine($titles, $urls);
        ksort($suggestions);

        $this->assertSame('lightsail', $query);
        $this->assertSame([
            'Lightsail rigging guide' => 'http://localhost/d/1-lightsail-rigging-guide',
            'Lightsail tuning' => 'http://localhost/d/2-lightsail-tuning',
        ], $suggestions);
        $this->assertCount(2, $descriptions);
    }

    #[Test]
    public function a_discussion_the_visitor_cannot_see_is_not_suggested(): void
    {
        $this->assertNotContains('Lightsail secrets', $this->suggest('lightsail')[1]);
    }

    #[Test]
    public function a_partly_typed_word_is_completed(): void
    {
        $this->assertSame(['Boat insurance claims'], $this->suggest('INSUR')[1]);
    }

    #[Test]
    public function a_word_is_completed_from_its_start_not_its_middle(): void
    {
        $this->assertSame([], $this->suggest('sura')[1]);
    }

    #[Test]
    public function every_word_typed_must_match(): void
    {
        $this->assertSame(['Lightsail tuning'], $this->suggest('lightsail tu')[1]);
    }

    #[Test]
    public function a_single_character_suggests_nothing(): void
    {
        $this->assertSame(['l', [], [], []], $this->suggest('l'));
    }
}
