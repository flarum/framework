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

/**
 * PostgreSQL rejects a query comparing an integer column with a number beyond
 * its range, so a filter given such an id must match nothing instead.
 */
class OutOfRangeFilterIdTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
            ],
        ]);
    }

    public static function filters(): array
    {
        $cases = [];

        foreach ([
            ['/api/posts', 'discussion'],
            ['/api/posts', 'id'],
            ['/api/posts', 'number'],
            ['/api/discussions', 'id'],
            ['/api/users', 'group'],
            ['/api/access-tokens', 'user'],
        ] as [$path, $filter]) {
            foreach (['2147483648', '99999999999999999999'] as $id) {
                $cases["$path $filter $id"] = [$path, $filter, $id];
            }
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('filters')]
    public function out_of_range_id_matches_nothing(string $path, string $filter, string $id)
    {
        $response = $this->send(
            $this->request('GET', $path, ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => [$filter => $id]])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertSame([], json_decode($body, true)['data']);
    }

    #[Test]
    #[DataProvider('filters')]
    public function negated_out_of_range_id_excludes_nothing(string $path, string $filter, string $id)
    {
        $unfiltered = $this->send(
            $this->request('GET', $path, ['authenticatedAs' => 1])
        );

        $response = $this->send(
            $this->request('GET', $path, ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => ["-$filter" => $id]])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $expected = array_column(json_decode((string) $unfiltered->getBody(), true)['data'], 'id');

        // Each authenticated request adds an access token, so later lists can only be longer.
        $this->assertNotEmpty($expected);
        $this->assertSame([], array_diff($expected, array_column(json_decode($body, true)['data'], 'id')));
    }

    #[Test]
    public function in_range_id_still_filters()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => ['discussion' => '1', 'number' => '1', 'id' => '1,2147483648']])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertSame(['1'], array_column(json_decode($body, true)['data'], 'id'));
    }
}
