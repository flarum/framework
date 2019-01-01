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

use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Discussion\Discussion;

class ListDiscussionsControllerTest extends ApiControllerTestCase
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

    /**
     * @test
     */
    public function can_search_for_author()
    {
        $user = $this->getNormalUser();

        $response = $this->callWith([], [
            'filter' => [
                'q' => 'author:'.$user->username.' foo'
            ],
            'include' => 'mostRelevantPost'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
