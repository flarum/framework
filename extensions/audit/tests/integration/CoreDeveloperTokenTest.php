<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use PHPUnit\Framework\Attributes\Test;

class CoreDeveloperTokenTest extends TestCase
{
    #[Test]
    public function developer_token_created()
    {
        // Admin (user 1) creates a developer token for themselves.
        $this->sendSuccessfulRequest('POST', '/api/access-tokens', [
            'json' => [
                'data' => [
                    'type' => 'access-tokens',
                    'attributes' => [
                        'title' => 'CI deploy key',
                    ],
                ],
            ],
        ], 201);

        $this->assertLogExists('developer_token_created', [
            'user_id' => 1,
            'title' => 'CI deploy key',
        ]);
    }
}
