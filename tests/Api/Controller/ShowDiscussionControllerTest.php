<?php

namespace Flarum\Tests\Api\Controller;

use Flarum\Api\Controller\ShowDiscussionController;
use Flarum\Discussion\Discussion;
use Flarum\Tests\Test\Concerns\RetrievesAuthorizedUsers;

class ShowDiscussionControllerTest extends ApiControllerTestCase
{
    use RetrievesAuthorizedUsers;

    protected $controller = ShowDiscussionController::class;

    /**
     * @var Discussion
     */
    protected $discussion;

    protected function init()
    {
        $this->discussion = Discussion::start(__CLASS__, $this->getNormalUser());
    }

    /**
     * @test
     */
    public function author_can_see_discussion()
    {
        $this->discussion->save();

        $this->actor = $this->getNormalUser();

        $response = $this->callWith([], ['id' => $this->discussion->id]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function guest_can_see_discussion()
    {
        $this->discussion->save();

        $response = $this->callWith([], ['id' => $this->discussion->id]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function guests_cannot_see_private_discussion()
    {
        $this->discussion->is_private = true;
        $this->discussion->save();

        $this->callWith([], ['id' => $this->discussion->id]);
    }
}
