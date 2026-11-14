<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames\Tests\integration;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Members are found by part of their nickname as well as their username.
 */
class SearchTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-nicknames');

        $this->prepareDatabase([
            User::class => [
                ['id' => 2, 'username' => 'jd123', 'nickname' => 'Jane Doe', 'email' => 'jd@machine.local', 'is_email_confirmed' => 1],
                ['id' => 3, 'username' => 'doeful', 'email' => 'doeful@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1],
            ],
        ]);
    }

    #[Test]
    public function matches_part_of_a_nickname(): void
    {
        $this->assertSame(['jd123'], $this->search('Jane'));
        $this->assertEqualsCanonicalizing(['jd123', 'doeful'], $this->search('doe'));
    }

    #[Test]
    public function names_starting_with_the_text_come_first(): void
    {
        $this->assertSame(['doeful', 'jd123'], $this->search('doe'));
    }

    /**
     * @return string[]
     */
    private function search(string $q): array
    {
        $response = $this->send(
            $this->request('GET', '/api/users', ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => ['q' => $q]])
        );

        $body = (string) $response->getBody();

        $this->assertSame(200, $response->getStatusCode(), $body);

        return array_map(fn (array $user) => $user['attributes']['username'], json_decode($body, true)['data']);
    }
}
