<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration;

use Carbon\Carbon;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class GroupRecipientUpgradeTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public static function versions(): array
    {
        return ['Messages 2.0' => [true], 'current 2.x' => [false]];
    }

    #[Test]
    #[DataProvider('versions')]
    public function existing_conversations_and_read_positions_survive_upgrade(bool $released): void
    {
        $this->extension('flarum-messages');
        $at = Carbon::now()->subDay();
        $this->prepareDatabase([
            User::class => [['id' => 3, 'username' => 'alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1]],
            Dialog::class => [
                ['id' => 90, 'type' => 'direct', 'first_message_id' => 90, 'last_message_id' => 90, 'created_at' => $at, 'last_message_at' => $at],
                ['id' => 91, 'type' => 'group', 'name' => $released ? null : 'Existing team', 'first_message_id' => 91, 'last_message_id' => 91, 'created_at' => $at, 'last_message_at' => $at],
            ],
            DialogMessage::class => [
                ['id' => 90, 'dialog_id' => 90, 'user_id' => 1, 'content' => '<t><p>Original message</p></t>', 'number' => 1, 'created_at' => $at],
                ['id' => 91, 'dialog_id' => 91, 'user_id' => 1, 'content' => '<t><p>Team message</p></t>', 'number' => 1, 'created_at' => $at],
            ],
            'dialog_user' => [
                ['dialog_id' => 90, 'user_id' => 1, 'joined_at' => $at, 'last_read_message_id' => 90],
                ['dialog_id' => 90, 'user_id' => 3, 'joined_at' => $at],
                ['dialog_id' => 91, 'user_id' => 1, 'joined_at' => $at, 'last_read_message_id' => 91],
                ['dialog_id' => 91, 'user_id' => 3, 'joined_at' => $at, 'last_read_message_id' => 91, 'last_read_at' => $at],
            ],
        ]);
        $schema = $this->database()->getSchemaBuilder();
        if (! in_array($this->database()->getDriverName(), ['sqlite', 'pgsql'], true)) {
            $this->markTestSkipped('Reconstructing historical schemas uses transactional DDL, which MySQL does not support.');
        }
        $files = [__DIR__.'/../../migrations/2026_11_08_000000_add_group_recipients.php'];
        if ($released) {
            // These are the only schema migrations added between v2.0.0 and this feature's base.
            array_unshift($files,
                __DIR__.'/../../migrations/2026_10_31_000000_add_type_to_dialog_messages.php',
                __DIR__.'/../../migrations/2026_10_31_000001_add_name_to_dialogs.php');
        }
        foreach (array_reverse($files) as $file) { (include $file)['down']($schema); }
        $this->assertFalse($schema->hasColumn('dialog_user', 'is_recipient'));
        if ($released) {
            $this->assertFalse($schema->hasColumn('dialog_messages', 'type'));
            $this->assertFalse($schema->hasColumn('dialogs', 'name'));
        }
        foreach ($files as $file) { (include $file)['up']($schema); }
        foreach ([90 => 0, 91 => 91] as $id => $readPosition) {
            $response = $this->send($this->request('GET', "/api/dialogs/$id", ['authenticatedAs' => 3]));
            $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
            $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];
            $this->assertSame($readPosition, $attributes['lastReadMessageId']);
            $this->assertSame($id === 90 ? 1 : 0, $attributes['unreadCount']);
            $this->assertTrue((bool) $this->database()->table('dialog_user')->where('dialog_id', $id)->where('user_id', 3)->value('is_recipient'));
        }
        $this->assertSame('<t><p>Original message</p></t>', $this->database()->table('dialog_messages')->where('id', 90)->value('content'));
        $this->assertSame(0, $this->database()->table('dialog_group')->count());
        $this->assertSame($released ? null : 'Existing team', Dialog::findOrFail(91)->name);
        $this->assertFalse(User::findOrFail(3)->hasPermission('dialog.messageGroups'));
    }
}
