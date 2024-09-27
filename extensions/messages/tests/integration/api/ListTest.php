<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;

class ListTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'astarion'],
                ['id' => 4, 'username' => 'gale'],
                ['id' => 5, 'username' => 'karlach'],
            ],
            Dialog::class => [
                ['id' => 102, 'type' => 'direct'],
                ['id' => 103, 'type' => 'direct'],
                ['id' => 104, 'type' => 'direct'],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 3, 'content' => 'Hello, Gale!'],
                ['id' => 103, 'dialog_id' => 102, 'user_id' => 4, 'content' => 'Hello, Astarion!'],
                ['id' => 104, 'dialog_id' => 103, 'user_id' => 3, 'content' => 'Hello, Karlach!'],
                ['id' => 105, 'dialog_id' => 103, 'user_id' => 5, 'content' => 'Hello, Astarion!'],
                ['id' => 106, 'dialog_id' => 104, 'user_id' => 4, 'content' => 'Hello, Karlach!'],
                ['id' => 107, 'dialog_id' => 104, 'user_id' => 5, 'content' => 'Hello, Gale!'],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => Carbon::now()],
                ['dialog_id' => 103, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 103, 'user_id' => 5, 'joined_at' => Carbon::now()],
                ['dialog_id' => 104, 'user_id' => 4, 'joined_at' => Carbon::now()],
                ['dialog_id' => 104, 'user_id' => 5, 'joined_at' => Carbon::now()],
            ],
        ]);
    }

    #[DataProvider('dialogsAccessProvider')]
    public function test_can_list_accessible_dialogs(int $actorId, array $visibleDialogs): void
    {
        $response = $this->send(
            $this->request('GET', '/api/dialogs', [
                'authenticatedAs' => $actorId,
            ])->withQueryParams(['include' => 'users'])
        );

        $json = $response->getBody()->getContents();
        $prettyJson = json_encode($json, JSON_PRETTY_PRINT);

        $this->assertEquals(200, $response->getStatusCode(), $prettyJson);
        $this->assertJson($json);

        $data = json_decode($json, true)['data'];

        $this->assertCount(count($visibleDialogs), $data);

        foreach ($visibleDialogs as $dialogId) {
            $ids = array_column($data, 'id');
            $this->assertContains((string) $dialogId, $ids, json_encode($ids, JSON_PRETTY_PRINT));
        }
    }

    public static function dialogsAccessProvider(): array
    {
        return [
            'Astarion can see dialogs with Gale and Karlach' => [3, [102, 103]],
            'Gale can see dialogs with Astarion and Karlach' => [4, [102, 104]],
            'Karlach can see dialogs with Astarion and Gale' => [5, [103, 104]],
        ];
    }

    #[DataProvider('dialogMessagesAccessProvider')]
    public function test_can_list_accessible_dialog_messages(int $actorId, array $visibleDialogMessages): void
    {
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', [
                'authenticatedAs' => $actorId,
            ])->withQueryParams(['include' => 'dialog']),
        );

        $json = $response->getBody()->getContents();
        $prettyJson = json_encode($json, JSON_PRETTY_PRINT);

        $this->assertEquals(200, $response->getStatusCode(), $prettyJson);
        $this->assertJson($json);

        $data = json_decode($json, true)['data'];
        $prettyJson = json_encode(json_decode($json), JSON_PRETTY_PRINT);

        $this->assertCount(count($visibleDialogMessages), $data, $prettyJson);

        foreach ($visibleDialogMessages as $dialogMessageId) {
            $ids = array_column($data, 'id');
            $this->assertContains((string) $dialogMessageId, $ids, json_encode($ids, JSON_PRETTY_PRINT));
        }
    }

    public static function dialogMessagesAccessProvider(): array
    {
        return [
            'Astarion can see messages in dialogs with Gale and Karlach' => [3, [102, 103, 104, 105]],
            'Gale can see messages in dialogs with Astarion and Karlach' => [4, [102, 103, 106, 107]],
            'Karlach can see messages in dialogs with Astarion and Gale' => [5, [104, 105, 106, 107]],
        ];
    }
}
