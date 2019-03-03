<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\Controller;

use Flarum\Api\Controller\CreateUserController;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Support\Arr;

class CreateUserControllerTest extends ApiControllerTestCase
{
    protected $controller = CreateUserController::class;

    protected $data = [
        'username' => 'test',
        'password' => 'too-obscure',
        'email' => 'test@machine.local'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
            ],
            'groups' => [
                $this->adminGroup(),
            ],
            'group_user' => [
                ['user_id' => 1, 'group_id' => 1],
            ],
        ]);
    }

    /**
     * @test
     * @expectedException \Illuminate\Validation\ValidationException
     * @expectedExceptionMessage The given data was invalid.
     */
    public function cannot_create_user_without_data()
    {
        $this->callWith();
    }

    /**
     * @test
     */
    public function can_create_user()
    {
        $response = $this->callWith($this->data);

        $this->assertEquals(201, $response->getStatusCode());

        /** @var User $user */
        $user = User::where('username', 'test')->firstOrFail();

        $this->assertEquals(0, $user->is_activated);

        foreach (Arr::except($this->data, 'password') as $property => $value) {
            $this->assertEquals($value, $user->{$property});
        }
    }

    /**
     * @test
     */
    public function admins_can_create_activated_users()
    {
        $this->actor = User::find(1);

        $response = $this->callWith(array_merge($this->data, [
            'isEmailConfirmed' => 1
        ]));

        $this->assertEquals(201, $response->getStatusCode());

        /** @var User $user */
        $user = User::where('username', 'test')->firstOrFail();

        $this->assertEquals(1, $user->is_email_confirmed);
    }

    /**
     * @test
     * @expectedException \Flarum\User\Exception\PermissionDeniedException
     */
    public function disabling_sign_up_prevents_user_creation()
    {
        /** @var SettingsRepositoryInterface $settings */
        $settings = app(SettingsRepositoryInterface::class);
        $settings->set('allow_sign_up', false);

        try {
            $this->callWith($this->data);
        } finally {
            $settings->set('allow_sign_up', true);
        }
    }
}
