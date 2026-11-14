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
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\Test;

/**
 * Two things the schema has to refuse, because the code allocating them can
 * race: a member in the same dialog twice, and two messages with the same
 * number in one dialog.
 */
class IntegrityConstraintsTest extends TestCase
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
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 4, 'content' => 'Hi', 'number' => 1],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now()],
            ],
        ]);

        $this->app();
    }

    #[Test]
    public function a_member_cannot_be_in_a_dialog_twice(): void
    {
        $this->expectException(QueryException::class);

        $this->database()->table('dialog_user')->insert(['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now()]);
    }

    #[Test]
    public function two_messages_cannot_share_a_number_in_a_dialog(): void
    {
        $this->expectException(QueryException::class);

        $this->database()->table('dialog_messages')->insert(['dialog_id' => 102, 'user_id' => 3, 'content' => 'Again', 'number' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
    }
}
