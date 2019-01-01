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

use Flarum\Api\Controller\CreatePostController;
use Flarum\Discussion\Discussion;
use Illuminate\Support\Arr;

class CreatePostControllerTest extends ApiControllerTestCase
{
    protected $controller = CreatePostController::class;

    protected $data = [
        'content' => 'reply with predetermined content for automated testing - too-obscure'
    ];

    /**
     * @var Discussion
     */
    protected $discussion;

    protected function init()
    {
        $this->actor = $this->getNormalUser();
        $this->discussion = Discussion::start(__CLASS__, $this->actor);

        $this->discussion->save();
    }

    /**
     * @test
     */
    public function can_create_reply()
    {
        $body = [];
        Arr::set($body, 'data.attributes', $this->data);
        Arr::set($body, 'data.relationships.discussion.data.id', $this->discussion->id);

        $response = $this->callWith($body);

        $this->assertEquals(201, $response->getStatusCode());
    }
}
