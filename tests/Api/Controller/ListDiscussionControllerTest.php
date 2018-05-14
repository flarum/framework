<?php

namespace Flarum\Tests\Api\Controller;

use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Discussion\Discussion;

class ListDiscussionControllerTest extends ApiControllerTestCase
{
    protected $controller = ListDiscussionsController::class;

    /**
     * @test
     */
    public function shows_index_for_guest()
    {
        $response = $this->callWith();

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(Discussion::count(), count($data['data']));
    }
}
