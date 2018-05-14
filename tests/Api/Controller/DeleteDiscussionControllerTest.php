<?php

namespace Flarum\Tests\Api\Controller;

use Flarum\Api\Controller\DeleteDiscussionController;
use Flarum\Discussion\Discussion;
use Flarum\Tests\Test\Concerns\RetrievesAuthorizedUsers;

class DeleteDiscussionControllerTest extends ApiControllerTestCase
{
    use RetrievesAuthorizedUsers;

    protected $controller = DeleteDiscussionController::class;
    protected $discussion;

    protected function init()
    {
        $this->discussion = Discussion::start(__CLASS__, $this->getNormalUser());

        $this->discussion->save();
    }

    /**
     * @test
     */
    public function admin_can_delete()
    {
        $this->actor = $this->getAdminUser();

        $response = $this->callWith([], ['id' => $this->discussion->id]);

        $this->assertEquals(204, $response->getStatusCode());
    }
}
