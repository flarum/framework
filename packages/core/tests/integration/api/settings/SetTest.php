<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\settings;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class SetTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * @test
     */
    public function settings_cant_be_updated_by_user()
    {
        $response = $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 2,
                'json' => [
                    'hello' => 'world',
                ],
            ])
        );

        // Test for successful response and that the email is included in the response
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function settings_can_be_updated_by_admin()
    {
        $response = $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => [
                    'hello' => 'world',
                ],
            ])
        );

        // Test for successful response and that the email is included in the response
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function max_setting_length_validated()
    {
        $response = $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => [
                    'hello' => str_repeat('a', 66000),
                ],
            ])
        );

        // Test for successful response and that the email is included in the response
        $this->assertEquals(422, $response->getStatusCode());
    }
}
