<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialog_messages;

use Carbon\Carbon;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class DeleteTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice'],
                ['id' => 4, 'username' => 'bob'],
            ],
            Dialog::class => [
                ['id' => 102, 'type' => 'direct', 'first_message_id' => 102, 'last_message_id' => 104],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 3, 'content' => 'First', 'number' => 1],
                ['id' => 103, 'dialog_id' => 102, 'user_id' => 3, 'content' => 'Second', 'number' => 2],
                ['id' => 104, 'dialog_id' => 102, 'user_id' => 3, 'content' => 'Third', 'number' => 3],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => Carbon::now()],
            ],
        ]);

        // Anything other than "until reply" reaches the delete without the
        // policy having loaded the dialog, which is the case the first/last
        // pointers used to be lost in.
        $this->setting('flarum-messages.allow_delete_own_messages', '-1');
    }

    protected function delete(int $messageId, int $actor = 3): int
    {
        return $this->send(
            $this->request('DELETE', '/api/dialog-messages/'.$messageId, ['authenticatedAs' => $actor])
        )->getStatusCode();
    }

    /**
     * Read through the query builder rather than the model, so this works
     * before the application has been booted by a request.
     */
    protected function dialogRow(): ?object
    {
        return $this->database()->table('dialogs')->where('id', 102)->first();
    }

    public function test_deleting_the_first_message_moves_the_pointer_to_the_next_one(): void
    {
        $this->assertEquals(204, $this->delete(102));

        $dialog = $this->dialogRow();

        // The foreign key nulls this column on delete, so it has to be
        // recomputed rather than left pointing at nothing.
        $this->assertEquals(103, $dialog->first_message_id);
        $this->assertEquals(104, $dialog->last_message_id);
    }

    public function test_deleting_the_last_message_moves_the_pointer_to_the_previous_one(): void
    {
        $this->assertEquals(204, $this->delete(104));

        $dialog = $this->dialogRow();

        $this->assertEquals(102, $dialog->first_message_id);
        $this->assertEquals(103, $dialog->last_message_id);
    }

    public function test_deleting_a_message_in_the_middle_leaves_both_pointers_alone(): void
    {
        $this->assertEquals(204, $this->delete(103));

        $dialog = $this->dialogRow();

        $this->assertEquals(102, $dialog->first_message_id);
        $this->assertEquals(104, $dialog->last_message_id);
    }

    public function test_deleting_the_only_message_deletes_the_dialog(): void
    {
        $this->assertEquals(204, $this->delete(102));
        $this->assertEquals(204, $this->delete(103));
        $this->assertEquals(204, $this->delete(104));

        $this->assertNull($this->dialogRow());
    }

    public function test_a_dialog_left_without_pointers_is_repaired_on_the_next_deletion(): void
    {
        // The state an affected forum is already in.
        $this->database()->table('dialogs')->where('id', 102)->update([
            'first_message_id' => null,
            'last_message_id' => null,
        ]);

        $this->assertEquals(204, $this->delete(103));

        $dialog = $this->dialogRow();

        $this->assertEquals(102, $dialog->first_message_id);
        $this->assertEquals(104, $dialog->last_message_id);
    }

    public function test_the_dialog_is_still_readable_after_its_first_message_is_deleted(): void
    {
        $this->delete(102);

        $response = $this->send(
            $this->request('GET', '/api/dialogs/102', ['authenticatedAs' => 3])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        // A null relationship here is what the message stream chokes on.
        $this->assertNotNull($data['data']['relationships']['firstMessage']['data'] ?? null);
        $this->assertEquals('103', $data['data']['relationships']['firstMessage']['data']['id']);
    }
}
