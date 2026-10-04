<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Deck\Tests\integration;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class LayoutTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-deck');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    public function test_a_member_can_save_their_layout(): void
    {
        $columns = [
            ['id' => 'a1', 'type' => 'following', 'width' => 'normal', 'row' => 0, 'params' => []],
            ['id' => 'b2', 'type' => 'tag', 'width' => 'wide', 'row' => 1, 'params' => ['slug' => 'general']],
        ];

        $this->saveLayout(2, $columns);

        $this->assertEquals($columns, $this->storedLayout(2));
    }

    public function test_resetting_clears_the_layout_back_to_the_default(): void
    {
        $this->saveLayout(2, [['id' => 'a', 'type' => 'all']]);
        $this->saveLayout(2, null);

        $this->assertNull($this->storedLayout(2));
    }

    public function test_invalid_columns_are_dropped_and_values_normalised(): void
    {
        $this->saveLayout(2, [
            ['id' => 'ok', 'type' => 'all', 'width' => 'huge', 'row' => 5, 'params' => ['q' => 'x', 'bad key' => 'y', 'n' => ['nested']]],
            ['id' => 'ok', 'type' => 'unread'],
            ['id' => '<script>', 'type' => 'all'],
            ['id' => 'c3', 'type' => '../etc'],
            'not a column',
        ]);

        $this->assertEquals(
            [['id' => 'ok', 'type' => 'all', 'width' => 'normal', 'row' => 0, 'params' => ['q' => 'x']]],
            $this->storedLayout(2)
        );
    }

    public function test_the_number_of_columns_is_capped_by_the_setting(): void
    {
        $this->setting('flarum-deck.max_columns', 2);

        $this->saveLayout(2, [
            ['id' => 'a', 'type' => 'all'],
            ['id' => 'b', 'type' => 'unread'],
            ['id' => 'c', 'type' => 'notifications'],
        ]);

        $this->assertEquals(['a', 'b'], array_column($this->storedLayout(2), 'id'));
    }

    public function test_members_without_permission_cannot_save_a_layout(): void
    {
        $this->database()->table('group_permission')->where('permission', 'deck.use')->delete();

        $this->saveLayout(2, [['id' => 'a', 'type' => 'all']]);

        $this->assertNull($this->storedLayout(2));
    }

    protected function saveLayout(int $userId, ?array $columns): void
    {
        $response = $this->send(
            $this->request('PATCH', "/api/users/$userId", [
                'authenticatedAs' => $userId,
                'json' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $userId,
                        'attributes' => [
                            'preferences' => ['deckColumns' => $columns],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    protected function storedLayout(int $userId): ?array
    {
        return User::query()->findOrFail($userId)->getPreference('deckColumns');
    }
}
