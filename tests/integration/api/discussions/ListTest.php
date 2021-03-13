<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;

class ListTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'lightsail in title', 'created_at' => Carbon::createFromDate(1985, 5, 21)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(1985, 5, 21)->toDateTimeString(), 'user_id' => 2, 'comment_count' => 1],
                ['id' => 3, 'title' => 'not in title', 'created_at' => Carbon::createFromDate(1995, 5, 21)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(1995, 5, 21)->toDateTimeString(), 'user_id' => 2, 'comment_count' => 1],
                ['id' => 4, 'title' => 'hidden', 'created_at' => Carbon::createFromDate(2005, 5, 21)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(2005, 5, 21)->toDateTimeString(), 'hidden_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::createFromDate(1985, 5, 21)->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>not in text</p></t>'],
                ['id' => 3, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1995, 5, 21)->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>lightsail in text</p></t>'],
                ['id' => 4, 'discussion_id' => 4, 'created_at' => Carbon::createFromDate(2005, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>lightsail in text</p></t>'],
            ],
            'users' => [
                $this->normalUser(),
            ]
        ]);
    }

    /**
     * Mark some discussions, but not others, as read to test that filter/gambit.
     */
    protected function read()
    {
        $user = User::find(2);
        $user->marked_all_as_read_at = Carbon::createFromDate(1990, 0, 0)->toDateTimeString();
        $user->save();
    }

    /**
     * @test
     */
    public function shows_index_for_guest()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(3, count($data['data']));
    }

    /**
     * @test
     */
    public function author_filter_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
            ->withQueryParams([
                'filter' => ['author' => 'normal'],
                'include' => 'mostRelevantPost',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEqualsCanonicalizing(['2', '3'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function author_filter_works_negated()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
            ->withQueryParams([
                'filter' => ['-author' => 'normal'],
                'include' => 'mostRelevantPost',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEquals(['1'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function created_filter_works_with_date()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
            ->withQueryParams([
                'filter' => ['created' => '1995-05-21'],
                'include' => 'mostRelevantPost',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEquals(['3'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function created_filter_works_negated_with_date()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
            ->withQueryParams([
                'filter' => ['-created' => '1995-05-21'],
                'include' => 'mostRelevantPost',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEqualsCanonicalizing(['1', '2'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function created_filter_works_with_range()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
            ->withQueryParams([
                'filter' => ['created' => '1980-05-21..2000-05-21'],
                'include' => 'mostRelevantPost',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEqualsCanonicalizing(['2', '3'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function created_filter_works_negated_with_range()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
            ->withQueryParams([
                'filter' => ['-created' => '1980-05-21..2000-05-21'],
                'include' => 'mostRelevantPost',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEquals(['1'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function hidden_filter_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
            ->withQueryParams([
                'filter' => ['hidden' => ''],
                'include' => 'mostRelevantPost',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEquals(['4'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function hidden_filter_works_negated()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
            ->withQueryParams([
                'filter' => ['-hidden' => ''],
                'include' => 'mostRelevantPost',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEqualsCanonicalizing(['1', '2', '3'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function unread_filter_works()
    {
        $this->app();
        $this->read();

        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams([
                    'filter' => ['unread' => ''],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEquals(['3'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function unread_filter_works_when_negated()
    {
        $this->app();
        $this->read();

        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams([
                    'filter' => ['-unread' => ''],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEqualsCanonicalizing(['1', '2'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function author_gambit_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
            ->withQueryParams([
                'filter' => ['q' => 'author:normal'],
                'include' => 'mostRelevantPost',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEqualsCanonicalizing(['2', '3'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function author_gambit_works_negated()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
            ->withQueryParams([
                'filter' => ['q' => '-author:normal'],
                'include' => 'mostRelevantPost',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEquals(['1'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function created_gambit_works_with_date()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
            ->withQueryParams([
                'filter' => ['q' => 'created:1995-05-21'],
                'include' => 'mostRelevantPost',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEquals(['3'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function created_gambit_works_negated_with_date()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
            ->withQueryParams([
                'filter' => ['q' => '-created:1995-05-21'],
                'include' => 'mostRelevantPost',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEqualsCanonicalizing(['1', '2'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function created_gambit_works_with_range()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
            ->withQueryParams([
                'filter' => ['q' => 'created:1980-05-21..2000-05-21'],
                'include' => 'mostRelevantPost',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEqualsCanonicalizing(['2', '3'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function created_gambit_works_negated_with_range()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
            ->withQueryParams([
                'filter' => ['q' => '-created:1980-05-21..2000-05-21'],
                'include' => 'mostRelevantPost',
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEquals(['1'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function hidden_gambit_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
                ->withQueryParams([
                    'filter' => ['q' => 'is:hidden'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEquals(['4'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function hidden_gambit_works_negated()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
                ->withQueryParams([
                    'filter' => ['q' => '-is:hidden'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEqualsCanonicalizing(['1', '2', '3'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function unread_gambit_works()
    {
        $this->app();
        $this->read();

        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams([
                    'filter' => ['q' => 'is:unread'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEquals(['3'], Arr::pluck($data, 'id'), 'IDs do not match');
    }

    /**
     * @test
     */
    public function unread_gambit_works_when_negated()
    {
        $this->app();
        $this->read();

        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams([
                    'filter' => ['q' => '-is:unread'],
                    'include' => 'mostRelevantPost',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $this->assertEqualsCanonicalizing(['1', '2'], Arr::pluck($data, 'id'), 'IDs do not match');
    }
}
