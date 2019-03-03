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

use Flarum\Api\Controller\CreateDiscussionController;
use Flarum\Discussion\Discussion;
use Flarum\User\User;
use Illuminate\Support\Arr;

class CreateDiscussionControllerTest extends ApiControllerTestCase
{
    protected $controller = CreateDiscussionController::class;

    protected $data = [
        'title' => 'test - too-obscure',
        'content' => 'predetermined content for automated testing - too-obscure'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->prepareDatabase([
            'discussions' => [],
            'posts' => [],
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
     */
    public function can_create_discussion()
    {
        $this->actor = User::find(1);

        $response = $this->callWith($this->data);

        $this->assertEquals(201, $response->getStatusCode());

        /** @var Discussion $discussion */
        $discussion = Discussion::where('title', $this->data['title'])->firstOrFail();
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals($this->data['title'], $discussion->title);
        $this->assertEquals($this->data['title'], array_get($data, 'data.attributes.title'));
    }

    /**
     * @test
     * @expectedException \Illuminate\Validation\ValidationException
     * @expectedExceptionMessage The given data was invalid.
     */
    public function cannot_create_discussion_without_content()
    {
        $this->actor = User::find(1);

        $data = Arr::except($this->data, 'content');

        $this->callWith($data);
    }

    /**
     * @test
     * @expectedException \Illuminate\Validation\ValidationException
     * @expectedExceptionMessage The given data was invalid.
     */
    public function cannot_create_discussion_without_title()
    {
        $this->actor = User::find(1);

        $data = Arr::except($this->data, 'title');

        $this->callWith($data);
    }
}
