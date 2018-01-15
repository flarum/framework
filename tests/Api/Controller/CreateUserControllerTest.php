<?php

namespace Flarum\Tests\Api\Controller;

use Flarum\Api\Controller\CreateUserController;
use Flarum\Tests\Test\TestCase;

class CreateUserControllerTest extends TestCase
{
    /**
     * @test
     */
    public function cannot_create_user_without_data()
    {
        $response = $this->call(CreateUserController::class);

        $this->assertEquals(422, $response->getStatusCode());
    }
}
