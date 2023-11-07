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
            'users' => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'papi', 'email' => 'papi@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'acme', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1],
            ],
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'lightsail in title', 'created_at' => Carbon::createFromDate(1985, 5, 21)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(1985, 5, 21)->toDateTimeString(), 'user_id' => 2, 'comment_count' => 1],
                ['id' => 3, 'title' => 'not in title', 'created_at' => Carbon::createFromDate(1995, 5, 21)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(1995, 5, 21)->toDateTimeString(), 'user_id' => 2, 'comment_count' => 1],
                ['id' => 4, 'title' => 'hidden', 'created_at' => Carbon::createFromDate(2005, 5, 21)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(2005, 5, 21)->toDateTimeString(), 'hidden_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],

                // A discussion with a private first post (which means the comment_count = 0 as well).
                // comment_count=0 discussions are also considered as hidden discussions.
                // @see HiddenFilterGambit
                ['id' => 5, 'title' => 'first post private', 'created_at' => Carbon::createFromDate(2007, 5, 21)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(2007, 5, 21)->toDateTimeString(), 'user_id' => 4, 'comment_count' => 0, 'first_post_id' => 5, 'is_private' => 0],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::createFromDate(1985, 5, 21)->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>not in text</p></t>'],
                ['id' => 3, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1995, 5, 21)->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>lightsail in text</p></t>'],
                ['id' => 4, 'discussion_id' => 4, 'created_at' => Carbon::createFromDate(2005, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>lightsail in text</p></t>'],

                // Private first post.
                ['id' => 5, 'discussion_id' => 5, 'created_at' => Carbon::createFromDate(2005, 5, 21)->toDateTimeString(), 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>lightsail in text</p></t>', 'is_private' => 1],
            ],
            'groups' => [
                ['id' => 100, 'name_singular' => 'Acme', 'name_plural' => 'Acme', 'is_hidden' => 0]
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 100]
            ],
            'group_permission' => [
                ['permission' => 'discussion.editPosts', 'group_id' => 100]
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
        $this->assertEquals(['5', '4'], Arr::pluck($data, 'id'), 'IDs do not match');
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
        $this->assertEquals(['5', '4'], Arr::pluck($data, 'id'), 'IDs do not match');
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

    /**
     * @dataProvider userViewDiscussionPrivateFirstPostDataProvider
     * @test
     */
    public function user_can_only_see_discussion_with_private_first_post_if_allowed(?int $authenticatedAs, bool $canSee)
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', compact('authenticatedAs'))
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $method = $canSee ? 'assertContains' : 'assertNotContains';
        $this->{$method}('5', Arr::pluck($body['data'], 'id'));
    }

    public function userViewDiscussionPrivateFirstPostDataProvider(): array
    {
        return [
            'admin can see discussions with private first posts' => [1, true],
            'guests users cannot see discussions with private first posts' => [null, false],
            'normal users cannot see discussions with private first posts' => [2, false],
            'users with discussion.editPosts perm can see discussions with private first posts' => [3, true],
            'author can see discussions with private first posts' => [4, true],
        ];
    }
}
