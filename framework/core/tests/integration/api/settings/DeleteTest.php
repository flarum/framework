<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\settings;

use Flarum\Settings\Event\Reset;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;

class DeleteTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            'settings' => [
                ['key' => 'foo', 'value' => 'bar'],
                ['key' => 'baz', 'value' => 'qux'],
                ['key' => 'keep_me', 'value' => 'untouched'],
            ],
        ]);
    }

    #[Test]
    public function guest_cannot_delete_settings()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/settings', [
                'json' => ['keys' => ['foo']],
            ])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    #[Test]
    public function user_cannot_delete_settings()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/settings', [
                'authenticatedAs' => 2,
                'json' => ['keys' => ['foo']],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->assertEquals('bar', $this->database()->table('settings')->where('key', 'foo')->value('value'));
    }

    #[Test]
    public function admin_can_delete_a_setting()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => ['keys' => ['foo']],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());

        $this->assertNull($this->database()->table('settings')->where('key', 'foo')->value('value'));
    }

    #[Test]
    public function admin_can_delete_multiple_settings()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => ['keys' => ['foo', 'baz']],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());

        $this->assertNull($this->database()->table('settings')->where('key', 'foo')->value('value'));
        $this->assertNull($this->database()->table('settings')->where('key', 'baz')->value('value'));
    }

    #[Test]
    public function deleting_settings_does_not_affect_other_settings()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => ['keys' => ['foo']],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());

        $this->assertEquals('untouched', $this->database()->table('settings')->where('key', 'keep_me')->value('value'));
    }

    #[Test]
    public function deleting_a_nonexistent_setting_is_successful()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => ['keys' => ['does_not_exist']],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
    }

    #[Test]
    public function deleting_with_empty_keys_is_successful()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => ['keys' => []],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
    }

    #[Test]
    public function settings_reset_event_is_dispatched_with_extension_id_and_keys()
    {
        /** @var Reset|null $firedEvent */
        $firedEvent = null;

        $this->app()->getContainer()->make(Dispatcher::class)->listen(Reset::class, function (Reset $event) use (&$firedEvent) {
            $firedEvent = $event;
        });

        $this->send(
            $this->request('DELETE', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => ['keys' => ['foo', 'baz'], 'extensionId' => 'acme-test'],
            ])
        );

        $this->assertNotNull($firedEvent);
        $this->assertEquals(1, $firedEvent->actor->id);
        $this->assertEquals('acme-test', $firedEvent->extensionId);
        $this->assertEquals(['foo', 'baz'], $firedEvent->keys);
    }
}
