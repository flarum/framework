<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Searching members matches part of a name, not only its start, so someone
 * can be found from whatever part of their name is remembered.
 */
class SearchTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                ['id' => 2, 'username' => 'normal', 'email' => 'normal@machine.local', 'is_email_confirmed' => 1],
                ['id' => 3, 'username' => 'malice', 'email' => 'malice@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'Alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1],
            ],
        ]);
    }

    #[Test]
    public function matches_part_of_a_username(): void
    {
        $this->assertEqualsCanonicalizing(['malice', 'Alice'], $this->search('lic'));
    }

    #[Test]
    public function usernames_starting_with_the_text_come_first(): void
    {
        $this->assertSame(['Alice', 'malice', 'normal'], $this->search('al'));
    }

    #[Test]
    public function an_explicit_sort_still_applies(): void
    {
        $this->assertSame(['normal', 'malice', 'Alice'], $this->search('al', ['sort' => '-username']));
    }

    /**
     * @return string[]
     */
    private function search(string $q, array $params = []): array
    {
        $response = $this->send(
            $this->request('GET', '/api/users', ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => ['q' => $q]] + $params)
        );

        $body = (string) $response->getBody();

        $this->assertSame(200, $response->getStatusCode(), $body);

        return array_map(fn (array $user) => $user['attributes']['username'], json_decode($body, true)['data']);
    }
}
