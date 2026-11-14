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
use Flarum\Post\Post;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

/**
 * Naming any includes turns off an endpoint's default ones, so a client that
 * wants both sends `withDefaultIncludes`.
 */
class WithDefaultIncludesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>something</p></t>'],
            ],
        ]);
    }

    #[Test]
    #[DataProvider('withoutTheParameter')]
    public function named_includes_replace_the_defaults_without_the_parameter(array $query)
    {
        $response = $this->get('/api/posts', ['include' => 'user', ...$query]);

        $this->assertEquals(['users'], $this->includedTypes($response));
    }

    public static function withoutTheParameter(): array
    {
        return [
            'absent' => [[]],
            '0' => [['withDefaultIncludes' => '0']],
            'false' => [['withDefaultIncludes' => 'false']],
        ];
    }

    #[Test]
    #[DataProvider('withTheParameter')]
    public function the_parameter_adds_the_defaults_to_named_includes_when_listing(string $value)
    {
        $response = $this->get('/api/posts', ['include' => 'user', 'withDefaultIncludes' => $value]);

        // `discussion` and `user.groups` are among the endpoint's defaults.
        $this->assertEquals(['discussions', 'groups', 'users'], $this->includedTypes($response));
    }

    public static function withTheParameter(): array
    {
        return [
            '1' => ['1'],
            'true' => ['true'],
        ];
    }

    #[Test]
    public function the_parameter_adds_the_defaults_to_named_includes_when_showing()
    {
        $response = $this->get('/api/posts/1', ['include' => 'user', 'withDefaultIncludes' => '1']);

        $this->assertEquals(['discussions', 'groups', 'users'], $this->includedTypes($response));
    }

    #[Test]
    public function the_parameter_changes_nothing_without_named_includes()
    {
        $defaults = $this->get('/api/posts', []);
        $withParameter = $this->get('/api/posts', ['withDefaultIncludes' => '1']);

        $this->assertEquals(
            json_decode($defaults->getBody()->getContents(), true),
            json_decode($withParameter->getBody()->getContents(), true)
        );
    }

    #[Test]
    public function named_includes_are_still_validated_with_the_parameter()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
                ->withQueryParams(['include' => 'notARelationship', 'withDefaultIncludes' => '1'])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    protected function get(string $path, array $query): ResponseInterface
    {
        $response = $this->send(
            $this->request('GET', $path, ['authenticatedAs' => 1])->withQueryParams($query)
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $response->getBody()->rewind();

        return $response;
    }

    /** @return string[] */
    protected function includedTypes(ResponseInterface $response): array
    {
        $types = array_unique(array_column(json_decode($response->getBody()->getContents(), true)['included'] ?? [], 'type'));
        sort($types);

        return $types;
    }
}
