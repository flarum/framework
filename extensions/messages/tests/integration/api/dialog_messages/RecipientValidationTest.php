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
use PHPUnit\Framework\Attributes\Test;

/**
 * Starting a conversation names its recipients, and those names are checked
 * before anything is written. Previously a made-up ID reached the database,
 * failed on the foreign key, and left behind a dialog with no messages.
 */
class RecipientValidationTest extends TestCase
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
                ['id' => 102, 'type' => 'direct'],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 4, 'content' => 'Hi', 'number' => 1, 'created_at' => Carbon::now()->subHour()],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::parse('2024-01-01 00:00:00')],
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => Carbon::parse('2024-01-01 00:00:00')],
            ],
        ]);

        // Boots the app, so the model queries below have a connection.
        $this->app();
    }

    private function start(int $as, array $users)
    {
        return $this->send(
            $this->request('POST', '/api/dialog-messages', [
                'authenticatedAs' => $as,
                'json' => ['data' => ['type' => 'dialog-messages', 'attributes' => ['content' => 'Hello', 'users' => $users]]],
            ])
        );
    }

    #[Test]
    public function a_recipient_that_does_not_exist_is_rejected_and_leaves_nothing_behind(): void
    {
        $dialogs = Dialog::count();

        $response = $this->start(3, [['id' => 999]]);

        $this->assertEquals(422, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertEquals($dialogs, Dialog::count(), 'No dialog should be created for a recipient that does not exist.');
    }

    #[Test]
    public function naming_only_yourself_is_rejected(): void
    {
        $dialogs = Dialog::count();

        $this->assertEquals(422, $this->start(3, [['id' => 3]])->getStatusCode());
        $this->assertEquals($dialogs, Dialog::count());
    }

    #[Test]
    public function messaging_an_existing_contact_again_keeps_their_original_joined_at(): void
    {
        $response = $this->start(3, [['id' => 4]]);

        $this->assertEquals(201, $response->getStatusCode());

        $joined = $this->database()->table('dialog_user')->where('dialog_id', 102)->where('user_id', 4)->value('joined_at');

        $this->assertEquals('2024-01-01 00:00:00', Carbon::parse($joined)->toDateTimeString());
    }
}
