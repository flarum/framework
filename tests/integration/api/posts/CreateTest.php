<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Mockery;
use Psr\Http\Message\ResponseInterface;

class CreateTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2],
            ],
            'posts' => [],
            'users' => [
                $this->normalUser(),
            ],
            'groups' => [
                $this->memberGroup(),
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 3],
            ],
            'group_permission' => [
                ['permission' => 'viewDiscussions', 'group_id' => 3],
            ]
        ]);
    }

    /**
     * @test
     */
    public function can_create_reply()
    {
        $response = $this->sendTestRequest();

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function cant_flood_posts()
    {
        $response = $this->sendTestRequest();
        $this->assertEquals(201, $response->getStatusCode());

        $response = $this->sendTestRequest();
        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function can_flood_posts_interval_set_to_0()
    {
        /** @var SettingsRepositoryInterface $settings */
        $settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);
        $mock = Mockery::mock(SettingsRepositoryInterface::class)->makePartial();
        $mock->shouldReceive('get')
            ->atLeast()->once()
            ->andReturnUsing(function ($arg) use ($settings) {
                if ($arg == 'post_flood_interval') {
                    return '-1'; // -1 due to consecutive requests being instantaneous
                }

                return $settings->get($arg);
            });
        $this->app()->getContainer()->bind(SettingsRepositoryInterface::class, function () use ($mock) {
            return $mock;
        });


        $response = $this->sendTestRequest();
        $this->assertEquals(201, $response->getStatusCode());

        $response = $this->sendTestRequest();
        $this->assertEquals(201, $response->getStatusCode());
    }

    private function sendTestRequest(): ResponseInterface
    {
        return $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => 'reply with predetermined content for automated testing - too-obscure',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 1]],
                        ],
                    ],
                ],
            ])
        );
    }
}
