<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * An id in the path is percent-encoded when it must be (an extension's string
 * id may be a URL) and may be when it need not: either way it names the
 * resource it decodes to.
 */
class EncodedIdTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'Encoded', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 0],
            ],
        ]);
    }

    #[Test]
    public function an_encoded_id_finds_the_resource()
    {
        // "%31" is "1".
        $response = $this->send($this->request('GET', '/api/discussions/%31', ['authenticatedAs' => 1]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('1', json_decode((string) $response->getBody(), true)['data']['id']);
    }

    #[Test]
    public function an_encoded_slug_finds_the_resource()
    {
        // "n%6Frmal" is "normal".
        $response = $this->send($this->request('GET', '/api/users/n%6Frmal', ['authenticatedAs' => 1])->withQueryParams(['bySlug' => '1']));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('2', json_decode((string) $response->getBody(), true)['data']['id']);
    }
}
