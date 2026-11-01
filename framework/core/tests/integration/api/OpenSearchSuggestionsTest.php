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
                ['id' => 6, 'title' => '[Solved] Carbon-fibre mast', 'slug' => 'solved-carbon-fibre-mast', 'user_id' => 1],
                ['id' => 7, 'title' => 'Überführung nach Kiel', 'slug' => 'uberfuhrung-nach-kiel', 'user_id' => 1],
                ['id' => 8, 'title' => 'Hafencafe oder Café am Kai', 'slug' => 'hafencafe-oder-cafe-am-kai', 'user_id' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'content' => '<t><p>How to rig it.</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'user_id' => 1, 'content' => '<t><p>How to tune it.</p></t>'],
                ['id' => 3, 'discussion_id' => 3, 'user_id' => 1, 'content' => '<t><p>Nothing to see.</p></t>'],
                ['id' => 4, 'discussion_id' => 4, 'user_id' => 1, 'content' => '<t><p>Private.</p></t>'],
                ['id' => 5, 'discussion_id' => 5, 'user_id' => 1, 'content' => '<t><p>Who covers what.</p></t>'],
                ['id' => 6, 'discussion_id' => 6, 'user_id' => 1, 'content' => '<t><p>Fixed.</p></t>'],
                ['id' => 7, 'discussion_id' => 7, 'user_id' => 1, 'content' => '<t><p>Ahoi.</p></t>'],
                ['id' => 8, 'discussion_id' => 8, 'user_id' => 1, 'content' => '<t><p>Kaffee.</p></t>'],
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

    #[Test]
    public function the_query_is_echoed_exactly_as_typed(): void
    {
        // Browsers drop suggestions whose query differs from what is typed.
        [$query, $titles] = $this->suggest('lightsail tu ');

        $this->assertSame('lightsail tu ', $query);
        $this->assertSame(['Lightsail tuning'], $titles);
    }

    #[Test]
    public function a_query_that_is_not_text_suggests_nothing(): void
    {
        $response = $this->send($this->request('GET', '/api/opensearch/suggestions')->withQueryParams(['q' => ['lightsail']]));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(['', [], [], []], json_decode((string) $response->getBody(), true));
    }

    #[Test]
    public function a_very_long_query_is_answered_from_its_first_words(): void
    {
        $words = array_map(fn (int $i) => "filler$i", range(1, 1500));

        // The first eight words match; the 1500 after them are ignored.
        [$query, $titles] = $this->suggest('lightsail tun'.str_repeat(' lightsail', 6).' '.implode(' ', $words));

        $this->assertStringEndsWith(' filler1500', $query);
        $this->assertSame(['Lightsail tuning'], $titles);
    }

    #[Test]
    public function a_word_after_punctuation_is_completed(): void
    {
        $this->assertSame(['[Solved] Carbon-fibre mast'], $this->suggest('solv')[1]);
        $this->assertSame(['[Solved] Carbon-fibre mast'], $this->suggest('fib')[1]);
        $this->assertSame(['[Solved] Carbon-fibre mast'], $this->suggest('carbon-fibre')[1]);
    }

    #[Test]
    public function a_word_with_accented_letters_matches_in_any_case(): void
    {
        $this->assertSame(['Überführung nach Kiel'], $this->suggest('überf')[1]);
        $this->assertSame(['Überführung nach Kiel'], $this->suggest('ÜBERFÜHRUNG')[1]);
    }

    #[Test]
    public function a_word_typed_without_its_accents_is_completed(): void
    {
        // MySQL, MariaDB and PostgreSQL find "Café" for "cafe", and so must the
        // check for where words start. Here "Hafencafe" lets SQLite find it too.
        $this->assertSame(['Hafencafe oder Café am Kai'], $this->suggest('cafe')[1]);
    }
}
