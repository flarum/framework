<?php

namespace Flarum\Tests\Api\Controller;

use Flarum\Api\Controller\CreateUserController;

class CreateUserControllerTest extends AbstractTestController
{
    protected $controller = CreateUserController::class;

    /**
     * @test
     */
    public function cannot_create_user_without_data()
    {
        $response = $this->callWith();

        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function can_create_user()
    {
        $response = $this->callWith([
            'username' => 'test',
            'password' => 'too-obscure',
            'email' => 'test@machine.local'
        ]);

        $this->assertEquals(201, $response->getStatusCode());
    }
}
