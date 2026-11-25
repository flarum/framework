<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialog_messages;

use Carbon\Carbon;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Messages asked for by id, as a conversation already on screen fetches its
 * messages again after a reconnect: only those, and only ones the member may
 * see.
 */
class IdFilterTest extends TestCase
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
                ['id' => 5, 'username' => 'carol'],
            ],
            Dialog::class => [
                ['id' => 201, 'type' => 'direct', 'first_message_id' => 201, 'last_message_id' => 203, 'last_message_at' => Carbon::now()],
            ],
            DialogMessage::class => [
                ['id' => 201, 'dialog_id' => 201, 'user_id' => 4, 'content' => '<t><p>One</p></t>', 'number' => 1],
                ['id' => 202, 'dialog_id' => 201, 'user_id' => 3, 'content' => '<t><p>Two</p></t>', 'number' => 2],
                ['id' => 203, 'dialog_id' => 201, 'user_id' => 4, 'content' => '<t><p>Three</p></t>', 'number' => 3],
            ],
            'dialog_user' => [
                ['dialog_id' => 201, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 201, 'user_id' => 4, 'joined_at' => Carbon::now()],
            ],
        ]);
    }

    protected function ids(int $as, string $filter): array
    {
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => $as])
                ->withQueryParams(['filter' => ['dialog' => '201', 'id' => $filter]])
        );

        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode(), json_encode($body));

        return array_column($body['data'], 'id');
    }

    #[Test]
    public function only_the_messages_asked_for_come_back(): void
    {
        $this->assertEqualsCanonicalizing(['201', '203'], $this->ids(3, '201,203'));
    }

    #[Test]
    public function messages_the_member_cannot_see_do_not(): void
    {
        $this->assertEquals([], $this->ids(5, '201,202,203'));
    }

    public static function outOfRangeFilters(): array
    {
        return [
            'id' => [['dialog' => '201', 'id' => '2147483648']],
            'dialog' => [['dialog' => '2147483648']],
        ];
    }

    /**
     * PostgreSQL rejects a query comparing an integer column with a number
     * beyond its range, so such an id must match nothing instead.
     */
    #[Test]
    #[DataProvider('outOfRangeFilters')]
    public function out_of_range_id_in_a_filter_matches_nothing(array $filter): void
    {
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 3])
                ->withQueryParams(['filter' => $filter])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertSame([], json_decode($body, true)['data']);
    }

    public static function outOfRangeNearPages(): array
    {
        return [
            'out-of-range dialog' => ['2147483648', '2', []],
            'out-of-range number' => ['201', '2147483648', ['203', '202', '201']],
        ];
    }

    #[Test]
    #[DataProvider('outOfRangeNearPages')]
    public function near_page_with_an_out_of_range_value_does_not_fail(string $dialog, string $near, array $expected): void
    {
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 3])
                ->withQueryParams(['filter' => ['dialog' => $dialog], 'page' => ['near' => $near]])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertSame($expected, array_column(json_decode($body, true)['data'], 'id'));
    }
}
