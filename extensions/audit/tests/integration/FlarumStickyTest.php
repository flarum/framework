<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Carbon\Carbon;

class FlarumStickyTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-sticky');

        $date = Carbon::parse('2021-01-01T12:00:00+00:00');

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => 'A', 'created_at' => $date],
                ['id' => 2, 'title' => 'B', 'created_at' => $date, 'is_sticky' => true],
            ],
        ]);
    }

    /**
     * @test
     */
    public function lock()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/discussions/1', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'isSticky' => true,
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('discussion.stickied', [
            'discussion_id' => 1,
        ]);
    }

    /**
     * @test
     */
    public function unlock()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/discussions/2', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'isSticky' => false,
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('discussion.unstickied', [
            'discussion_id' => 2,
        ]);
    }
}
