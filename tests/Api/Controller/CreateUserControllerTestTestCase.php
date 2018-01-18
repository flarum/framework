<?php

namespace Flarum\Tests\Api\Controller;

use Flarum\Api\Controller\CreateUserController;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tests\Test\Concerns\RetrievesAuthorizedUsers;
use Flarum\User\User;
use Illuminate\Support\Arr;

class CreateUserControllerTest extends AbstractTestController
{
    use RetrievesAuthorizedUsers;

    protected $controller = CreateUserController::class;

    protected $data = [
        'username' => 'test',
        'password' => 'too-obscure',
        'email' => 'test@machine.local'
    ];

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
        $this->actor = $this->getAdminUser();

        $response = $this->callWith(array_merge($this->data, [
            'isActivated' => 1
        ]));

        $this->assertEquals(201, $response->getStatusCode());

        /** @var User $user */
        $user = User::where('username', 'test')->firstOrFail();

        $this->assertEquals(1, $user->is_activated);

    }

    /**
     * @test
     * @expectedException \Flarum\User\Exception\PermissionDeniedException
     */
    public function disabling_sign_up_prevents_user_creation()
    {
        /** @var SettingsRepositoryInterface $settings */
        $settings = $this->app->make(SettingsRepositoryInterface::class);
        $settings->set('allow_sign_up', false);

        try {
            $this->callWith($this->data);
        } finally {
            $settings->set('allow_sign_up', true);
        }
    }

    public function tearDown()
    {
        User::where('username', $this->data['username'])->delete();
        parent::tearDown();
    }
}
