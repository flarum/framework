<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialog_messages;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

/**
 * Searching private messages by what they say, only ever within the
 * conversations the searcher is part of.
 */
class SearchTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        // Before database(), which boots the app.
        $this->extension('flarum-messages');

        $this->database()->rollBack();

        // FULLTEXT indexing does not happen inside a transaction, so this data is
        // inserted outside one and cleaned up explicitly in tearDown().
        $now = Carbon::now();

        $this->database()->table('users')->insert($this->rowsThroughFactory(User::class, [
            ['id' => 3, 'username' => 'alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1],
            ['id' => 4, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1],
            ['id' => 5, 'username' => 'carol', 'email' => 'carol@machine.local', 'is_email_confirmed' => 1],
        ]));

        // The admin talks with alice; bob and carol talk without the admin.
        $this->database()->table('dialogs')->insert([
            ['id' => 201, 'type' => 'direct', 'created_at' => $now],
            ['id' => 202, 'type' => 'direct', 'created_at' => $now],
        ]);

        $this->database()->table('dialog_messages')->insert([
            ['id' => 201, 'dialog_id' => 201, 'user_id' => 3, 'number' => 1, 'content' => '<t><p>The pineapple arrives tomorrow</p></t>', 'created_at' => $now],
            ['id' => 202, 'dialog_id' => 201, 'user_id' => 1, 'number' => 2, 'content' => '<t><p>Nothing relevant here</p></t>', 'created_at' => $now],
            ['id' => 203, 'dialog_id' => 202, 'user_id' => 4, 'number' => 1, 'content' => '<t><p>Pineapple secrets between us</p></t>', 'created_at' => $now],
        ]);

        $this->database()->table('dialog_user')->insert([
            ['dialog_id' => 201, 'user_id' => 1, 'joined_at' => $now],
            ['dialog_id' => 201, 'user_id' => 3, 'joined_at' => $now],
            ['dialog_id' => 202, 'user_id' => 4, 'joined_at' => $now],
            ['dialog_id' => 202, 'user_id' => 5, 'joined_at' => $now],
        ]);

        $this->database()->beginTransaction();

        $this->populateDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->database()->table('dialog_user')->whereIn('dialog_id', [201, 202])->delete();
        $this->database()->table('dialog_messages')->whereIn('dialog_id', [201, 202])->delete();
        $this->database()->table('dialogs')->whereIn('id', [201, 202])->delete();
        $this->database()->table('users')->whereIn('id', [3, 4, 5])->delete();
    }

    /**
     * @return string[]
     */
    private function search(string $query, int $as): array
    {
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => $as])
                ->withQueryParams(['filter' => ['q' => $query]])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        return Arr::pluck(json_decode((string) $response->getBody(), true)['data'], 'id');
    }

    #[Test]
    public function a_member_finds_their_own_messages_by_what_they_say(): void
    {
        $this->assertEqualsCanonicalizing(['201'], $this->search('pineapple', 1));
    }

    // Admins included: there is no permission that opens other people's conversations.
    #[Test]
    public function messages_in_other_peoples_conversations_are_never_found(): void
    {
        $this->assertEqualsCanonicalizing(['203'], $this->search('pineapple', 4));
        $this->assertNotContains('203', $this->search('pineapple', 1));
    }

    #[Test]
    public function a_word_nobody_wrote_finds_nothing(): void
    {
        $this->assertEquals([], $this->search('zeppelin', 1));
    }

    #[Test]
    public function guests_cannot_search_messages(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages')->withQueryParams(['filter' => ['q' => 'pineapple']])
        );

        $this->assertEquals(401, $response->getStatusCode());
    }
}
