<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\groups;

use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class OrderTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Group::class => [
                ['id' => 5, 'name_singular' => 'Developer',  'name_plural' => 'Developers',  'is_hidden' => false, 'position' => 5],
                ['id' => 6, 'name_singular' => 'Subscriber', 'name_plural' => 'Subscribers', 'is_hidden' => false, 'position' => 6],
                ['id' => 7, 'name_singular' => 'VIP',        'name_plural' => 'VIPs',        'is_hidden' => false, 'position' => 7],
            ],
        ]);
    }

    #[Test]
    public function admin_can_reorder_groups()
    {
        $response = $this->send(
            $this->request('POST', '/api/groups/order', [
                'authenticatedAs' => 1,
                'json' => [
                    'order' => [6, 5],
                ],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        $this->assertSame(1, Group::findOrFail(5)->position, 'Group 5 should be moved to position 1');
        $this->assertSame(0, Group::findOrFail(6)->position, 'Group 6 should be moved to position 0');

        $this->assertNull(Group::findOrFail(7)->position, 'Group 7 should have null position when not included');
    }

    #[Test]
    public function non_admin_cannot_reorder_groups()
    {
        $response = $this->send(
            $this->request('POST', '/api/groups/order', [
                'authenticatedAs' => 2,
                'json' => [
                    'order' => [6, 5],
                ],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());

        $this->assertSame(5, Group::findOrFail(5)->position);
        $this->assertSame(6, Group::findOrFail(6)->position);
        $this->assertSame(7, Group::findOrFail(7)->position);
    }

    #[Test]
    public function rejects_missing_order_payload()
    {
        $response = $this->send(
            $this->request('POST', '/api/groups/order', [
                'authenticatedAs' => 1,
                'json' => [
                    // empty payload
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());

        $this->assertSame(5, Group::findOrFail(5)->position);
        $this->assertSame(6, Group::findOrFail(6)->position);
        $this->assertSame(7, Group::findOrFail(7)->position);
    }

    #[Test]
    public function rejects_null_order()
    {
        $response = $this->send(
            $this->request('POST', '/api/groups/order', [
                'authenticatedAs' => 1,
                'json' => [
                    'order' => null,
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());

        $this->assertSame(5, Group::findOrFail(5)->position);
        $this->assertSame(6, Group::findOrFail(6)->position);
        $this->assertSame(7, Group::findOrFail(7)->position);
    }

    #[Test]
    public function rejects_malformed_order()
    {
        $response = $this->send(
            $this->request('POST', '/api/groups/order', [
                'authenticatedAs' => 1,
                'json' => [
                    'order' => 'not-an-array',
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());

        $this->assertSame(5, Group::findOrFail(5)->position);
        $this->assertSame(6, Group::findOrFail(6)->position);
        $this->assertSame(7, Group::findOrFail(7)->position);
    }
}
