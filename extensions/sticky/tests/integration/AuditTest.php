<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky\Tests\integration;

use Carbon\Carbon;
use Flarum\Audit\Tests\integration\InteractsWithAuditLog;
use Flarum\Discussion\Discussion;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuditTest extends TestCase
{
    use InteractsWithAuditLog;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpAuditLog();

        $this->extension('flarum-audit', 'flarum-sticky');

        $date = Carbon::parse('2021-01-01T12:00:00+00:00');

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'A', 'created_at' => $date],
                ['id' => 2, 'title' => 'B', 'created_at' => $date, 'is_sticky' => true],
            ],
        ]);
    }

    #[Test]
    public function stickied()
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

    #[Test]
    public function unstickied()
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
