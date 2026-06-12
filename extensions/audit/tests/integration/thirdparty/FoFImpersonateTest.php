<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration\thirdparty;

use Flarum\Audit\Tests\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class FoFImpersonateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-impersonate');

        $this->prepareDatabase([
            User::class => [
                [
                    'id' => 3,
                    'username' => 'user3',
                    'email' => 'user3@example.com',
                ],
            ],
        ]);
    }

    #[Test]
    public function impersonate()
    {
        $adminSession = $this->sendForumCsrfRequest('POST', '/login', [
            'json' => [
                'identification' => 'admin',
                'password' => 'password',
            ],
        ]);

        // We can't use authenticateAs because Impersonate only works with sessions and not access tokens
        $response = $this->send($this->request('POST', '/api/impersonate', [
            'cookiesFrom' => $adminSession,
            'json' => [
                'data' => [
                    'attributes' => [
                        'userId' => 3,
                        'reason' => 'because', // Currently not optional, it would result in undefined index if not included
                    ],
                ],
            ],
        ])->withAddedHeader('X-CSRF-Token', $adminSession->getHeaderLine('X-CSRF-Token')));

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertLogExists('user.impersonated', [
            'user_id' => 3,
            'reason' => 'because',
        ]);
    }
}
