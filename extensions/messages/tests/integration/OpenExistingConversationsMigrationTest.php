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
use Illuminate\Database\Schema\Builder;
use PHPUnit\Framework\Attributes\Test;

/**
 * On upgrade, conversations in which someone allowed to message users without
 * messaging permission has already sent a message are opened to replies, as
 * sending one now would.
 */
class OpenExistingConversationsMigrationTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $at = Carbon::now()->subDay();

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1],
                ['id' => 6, 'username' => 'moderator', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
            ],
            'group_user' => [
                ['user_id' => 6, 'group_id' => 4],
            ],
            Dialog::class => [
                ['id' => 301, 'type' => 'direct', 'created_at' => $at],
                ['id' => 302, 'type' => 'direct', 'created_at' => $at],
                ['id' => 303, 'type' => 'direct', 'created_at' => $at],
                ['id' => 304, 'type' => 'direct', 'created_at' => $at],
            ],
            DialogMessage::class => [
                // The moderator wrote to alice.
                ['id' => 301, 'dialog_id' => 301, 'user_id' => 6, 'content' => '<t><p>Hi</p></t>', 'number' => 1, 'created_at' => $at],
                // The admin wrote to bob.
                ['id' => 302, 'dialog_id' => 302, 'user_id' => 1, 'content' => '<t><p>Hi</p></t>', 'number' => 1, 'created_at' => $at],
                // Two members.
                ['id' => 303, 'dialog_id' => 303, 'user_id' => 3, 'content' => '<t><p>Hi</p></t>', 'number' => 1, 'created_at' => $at],
                // Alice wrote to the moderator, who never replied.
                ['id' => 304, 'dialog_id' => 304, 'user_id' => 3, 'content' => '<t><p>Hi</p></t>', 'number' => 1, 'created_at' => $at],
            ],
            'dialog_user' => [
                ['dialog_id' => 301, 'user_id' => 6, 'joined_at' => $at],
                ['dialog_id' => 301, 'user_id' => 3, 'joined_at' => $at],
                ['dialog_id' => 302, 'user_id' => 1, 'joined_at' => $at],
                ['dialog_id' => 302, 'user_id' => 4, 'joined_at' => $at],
                ['dialog_id' => 303, 'user_id' => 3, 'joined_at' => $at],
                ['dialog_id' => 303, 'user_id' => 4, 'joined_at' => $at],
                ['dialog_id' => 304, 'user_id' => 3, 'joined_at' => $at],
                ['dialog_id' => 304, 'user_id' => 6, 'joined_at' => $at],
            ],
        ]);
    }

    protected function migrate(): void
    {
        $migration = include __DIR__.'/../../migrations/2026_10_07_000003_open_existing_staff_conversations.php';

        /** @var Builder $schema */
        $schema = $this->app()->getContainer()->make('db')->getSchemaBuilder();

        $migration['up']($schema);
    }

    /**
     * @return int[]
     */
    protected function openDialogs(): array
    {
        return Dialog::query()->where('anyone_can_reply', true)->orderBy('id')->pluck('id')->all();
    }

    #[Test]
    public function conversations_an_admin_or_a_permission_holder_wrote_in_are_opened(): void
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'dialog.messageUsersWithoutPermission', 'group_id' => 4],
            ],
        ]);

        $this->migrate();

        $this->assertSame([301, 302], $this->openDialogs());
    }

    #[Test]
    public function with_the_permission_granted_to_no_group_only_admins_count(): void
    {
        $this->migrate();

        $this->assertSame([302], $this->openDialogs());
    }
}
