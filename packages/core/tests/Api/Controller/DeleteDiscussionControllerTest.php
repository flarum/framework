<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
