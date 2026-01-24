<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Tests\integration\api\users;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class SuspendUserTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-suspend');

        $this->prepareDatabase([
            User::class => [
                ['id' => 1, 'username' => 'Muralf', 'email' => 'muralf@machine.local', 'is_email_confirmed' => 1],
                $this->normalUser(),
                ['id' => 3, 'username' => 'acme', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 5, 'name_singular' => 'Acme', 'name_plural' => 'Acme', 'is_hidden' => 0]
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 5]
            ],
            'group_permission' => [
                ['permission' => 'user.suspend', 'group_id' => 5]
            ]
        ]);
    }

    #[Test]
    #[DataProvider('allowedToSuspendUser')]
    public function can_suspend_user_if_allowed(?int $authenticatedAs, int $targetUserId, ?string $reason, ?string $message)
    {
        $response = $this->sendSuspensionRequest($authenticatedAs, $targetUserId, $reason, $message);

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
    }

    #[Test]
    #[DataProvider('unallowedToSuspendUser')]
    public function cannot_suspend_user_if_not_allowed(?int $authenticatedAs, int $targetUserId, ?string $reason, ?string $message)
    {
        $response = $this->sendSuspensionRequest($authenticatedAs, $targetUserId, $reason, $message);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public static function allowedToSuspendUser(): array
    {
        return [
            // Admin can suspend - test all combinations of reason and message
            [1, 2, 'Bad behavior', 'You have been suspended.'],
            [1, 2, 'Bad behavior', null],
            [1, 2, null, 'You have been suspended.'],
            [1, 2, null, null],
            // User with permission can suspend - test all combinations
            [3, 2, 'Violation of rules', 'Suspended for breaking rules.'],
            [3, 2, 'Violation of rules', null],
            [3, 2, null, 'Suspended for breaking rules.'],
            [3, 2, null, null],
        ];
    }

    public static function unallowedToSuspendUser(): array
    {
        return [
            [1, 1, null, null],
            [2, 2, null, null],
            [2, 3, null, null],
            [3, 3, null, null],
            [3, 1, null, null],
        ];
    }

    protected function sendSuspensionRequest(?int $authenticatedAs, int $targetUserId, ?string $reason = null, ?string $message = null): ResponseInterface
    {
        return $this->send(
            $this->request('PATCH', "/api/users/$targetUserId", [
                'authenticatedAs' => $authenticatedAs,
                'json' => [
                    'data' => [
                        'type' => 'users',
                        'attributes' => [
                            'suspendedUntil' => Carbon::now()->addDay(),
                            'suspendReason' => $reason,
                            'suspendMessage' => $message,
                        ]
                    ]
                ]
            ])
        );
    }
}
