<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Steps;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Install\AdminUser;
use Flarum\Install\Step;
use Illuminate\Database\ConnectionInterface;

class CreateAdminUser implements Step
{
    public function __construct(
        private readonly ConnectionInterface $database,
        private readonly AdminUser $admin,
        private readonly ?string $accessToken = null
    ) {
    }

    public function getMessage(): string
    {
        return 'Creating admin user '.$this->admin->getUsername();
    }

    public function run(): void
    {
        $uid = $this->database->table('users')->insertGetId(
            $this->admin->getAttributes()
        );

        $this->database->table('group_user')->insert([
            'user_id' => $uid,
            'group_id' => Group::ADMINISTRATOR_ID,
        ]);

        if ($this->accessToken) {
            $this->database->table('access_tokens')->insert([
                'type' => 'session_remember',
                'token' => $this->accessToken,
                'user_id' => $uid,
                'created_at' => Carbon::now(),
                'last_activity_at' => Carbon::now(),
            ]);
        }
    }
}
