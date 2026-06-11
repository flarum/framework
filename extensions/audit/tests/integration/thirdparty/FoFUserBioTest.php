<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration\thirdparty;

use Flarum\Audit\Tests\integration\TestCase;

class FoFUserBioTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-user-bio');

        $this->prepareDatabase([
            'users' => [
                [
                    'id' => 3,
                    'username' => 'user3',
                    'email' => 'user3@example.com',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function update()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/users/3', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'bio' => 'Hello World',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('user.bio_changed', [
            'user_id' => 3,
        ]);
    }
}
