<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\precognition;

require_once __DIR__.'/PrecognitionTestHelpers.php';

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

class HeaderAndRouteTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use PrecognitionTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Public', 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'created_at' => Carbon::now()],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
            ],
        ]);
    }

    protected function createGroupRequest(): ServerRequestInterface
    {
        return $this->jsonRequest('POST', '/api/groups', ['data' => ['type' => 'groups', 'attributes' => [
            'nameSingular' => 'precog', 'namePlural' => 'precogs',
        ]]], 1);
    }

    public static function acceptedValues(): array
    {
        return [
            'upper case' => [['TRUE']],
            'padded' => [["\t true  "]],
            'duplicated header' => [['true', 'true']],
        ];
    }

    #[Test]
    #[DataProvider('acceptedValues')]
    public function true_values_are_precognitive_and_write_nothing(array $values)
    {
        $request = $this->createGroupRequest();

        foreach ($values as $value) {
            $request = $request->withAddedHeader('Precognition', $value);
        }

        $response = $this->send($request);

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals(0, Group::query()->where('name_singular', 'precog')->count());
    }

    public static function malformedValues(): array
    {
        return [
            '1' => ['1'],
            'yes' => ['yes'],
            'true, false' => ['true, false'],
            'true,' => ['true,'],
        ];
    }

    #[Test]
    #[DataProvider('malformedValues')]
    public function other_values_are_rejected_without_writing(string $value)
    {
        $response = $this->send($this->createGroupRequest()->withHeader('Precognition', $value));

        $this->assertEquals(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('precognition_not_supported', $this->errorCode($response));
        $this->assertEquals(0, Group::query()->where('name_singular', 'precog')->count());
    }

    /**
     * [method, path, actor, body, headers].
     */
    public static function unsupportedRoutes(): array
    {
        return [
            'list endpoint' => ['GET', '/api/discussions', 2, null],
            'delete endpoint' => ['DELETE', '/api/discussions/1', 1, null],
            'delete via method override' => ['POST', '/api/posts/1', 1, null, ['X-HTTP-Method-Override' => 'DELETE']],
            'non-resource route' => ['POST', '/api/settings', 1, ['forum_title' => 'changed']],
        ];
    }

    #[Test]
    #[DataProvider('unsupportedRoutes')]
    public function unsupported_routes_are_rejected_without_running(string $method, string $path, ?int $as, ?array $json, array $headers = [])
    {
        $request = $this->precognitive($this->jsonRequest($method, $path, $json, $as));

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $forumTitle = $this->database()->table('settings')->where('key', 'forum_title')->value('value');

        $response = $this->send($request);

        $this->assertEquals(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('precognition_not_supported', $this->errorCode($response));
        $this->assertEquals('true', $response->getHeaderLine('Precognition'));
        $this->assertNotNull(Discussion::query()->find(1));
        $this->assertNotNull(Post::query()->find(1));
        $this->assertEquals($forumTitle, $this->database()->table('settings')->where('key', 'forum_title')->value('value'));
    }

    /**
     * CheckCsrfToken runs before HandlePrecognitiveRequests.
     */
    #[Test]
    public function session_authenticated_requests_still_need_a_csrf_token()
    {
        $request = $this->precognitive($this->jsonRequest('PATCH', '/api/users/2', [
            'data' => ['type' => 'users', 'id' => '2', 'attributes' => ['username' => 'bad name!']],
        ], 2))->withoutAttribute('bypassCsrfToken');

        $response = $this->send($request);

        $this->assertEquals(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('csrf_token_mismatch', $this->errorCode($response));
    }

    /**
     * Validate-Only narrows validation, not authorization: a field the actor
     * can't write is refused even when it isn't listed.
     */
    #[Test]
    public function validate_only_does_not_skip_field_authorization()
    {
        $response = $this->send($this->precognitive($this->jsonRequest('PATCH', '/api/users/2', [
            'data' => ['type' => 'users', 'id' => '2', 'attributes' => ['isEmailConfirmed' => true, 'displayName' => 'x']],
        ], 2), 'displayName'));

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function precognitive_422_body_equals_the_normal_422_body()
    {
        $body = ['data' => ['type' => 'groups', 'attributes' => ['nameSingular' => str_repeat('x', 300), 'namePlural' => '']]];

        $normal = $this->send($this->jsonRequest('POST', '/api/groups', $body, 1));
        $precognitive = $this->send($this->precognitive($this->jsonRequest('POST', '/api/groups', $body, 1)));

        $this->assertEquals(422, $normal->getStatusCode());
        $this->assertEquals(422, $precognitive->getStatusCode());
        $this->assertEquals((string) $normal->getBody(), (string) $precognitive->getBody());
    }
}
