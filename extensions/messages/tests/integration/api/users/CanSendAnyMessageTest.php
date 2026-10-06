<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\users;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Whether a user can be messaged is shown to those who could message them.
 * It used to be computed for every user in every response, which cost a
 * permissions lookup each and told anyone, guests included, who was suspended.
 */
class CanSendAnyMessageTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'alice'],
            ],
        ]);
    }

    private function attributes(?int $as, int $user): array
    {
        $response = $this->send($this->request('GET', "/api/users/$user", $as ? ['authenticatedAs' => $as] : []));

        return json_decode($response->getBody()->getContents(), true)['data']['attributes'];
    }

    #[Test]
    public function a_guest_is_not_told_who_can_be_messaged(): void
    {
        $this->assertArrayNotHasKey('canSendAnyMessage', $this->attributes(null, 3));
    }

    #[Test]
    public function a_member_who_can_message_sees_whether_others_can(): void
    {
        $this->assertTrue($this->attributes(2, 3)['canSendAnyMessage']);
    }

    #[Test]
    public function a_member_sees_their_own(): void
    {
        $this->assertTrue($this->attributes(2, 2)['canSendAnyMessage']);
    }
}
