<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ResetNotificationPreferencesTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser() + [
                    'preferences' => json_encode([
                        'notify_discussionRenamed_alert' => false,
                        'notify_discussionRenamed_email' => true,
                        'discloseOnline' => false,
                    ]),
                ],
                [
                    'id' => 3,
                    'username' => 'normal2',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', // BCrypt hash for "too-obscure"
                    'email' => 'normal2@machine.local',
                    'is_email_confirmed' => 1,
                ],
            ],
        ]);
    }

    #[Test]
    public function notification_preferences_read_as_their_defaults_afterwards()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/users/2/notification-preferences', ['authenticatedAs' => 2])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $preferences = json_decode($response->getBody()->getContents(), true)['data']['attributes']['preferences'];

        // A member who never changed anything reads every preference as its default.
        $defaults = json_decode($this->send(
            $this->request('GET', '/api/users/3', ['authenticatedAs' => 3])
        )->getBody()->getContents(), true)['data']['attributes']['preferences'];

        $notifications = array_filter($defaults, fn (string $key) => str_starts_with($key, 'notify_'), ARRAY_FILTER_USE_KEY);

        $this->assertNotEmpty($notifications);

        foreach ($notifications as $key => $default) {
            $this->assertSame($default, $preferences[$key], "$key should be back to its default");
        }
    }

    #[Test]
    public function other_preferences_are_untouched_and_nothing_else_is_stored()
    {
        $this->send(
            $this->request('DELETE', '/api/users/2/notification-preferences', ['authenticatedAs' => 2])
        );

        $stored = json_decode(User::query()->find(2)->getAttributes()['preferences'], true);

        // The reset removes the notification keys in one save; it does not write
        // the defaults of every other preference back as stored values.
        $this->assertSame(['discloseOnline' => false], $stored);
    }

    #[Test]
    public function another_member_cannot_reset_them()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/users/2/notification-preferences', ['authenticatedAs' => 3])
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse(User::query()->find(2)->getPreference('notify_discussionRenamed_alert'));
    }

    #[Test]
    public function a_guest_cannot_reset_them()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/users/2/notification-preferences')
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(401, $response->getStatusCode(), (string) $response->getBody());
    }
}
