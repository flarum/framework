<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Subscriptions\Extend\Subscription;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for SubscriptionFilter — verifies that filter[subscription]=following
 * and filter[subscription]=ignoring correctly constrain the discussion list,
 * including negation. These act as a regression guard before any localization
 * alias work is introduced.
 */
class SubscriptionFilterTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-subscriptions');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'acme', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Followed by normal', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'Ignored by normal', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 1],
                ['id' => 3, 'title' => 'No subscription', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 3, 'comment_count' => 1],
                ['id' => 10, 'title' => 'Also followed by acme', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 10, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo</p></t>'],
                ['id' => 2, 'number' => 1, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo</p></t>'],
                ['id' => 3, 'number' => 1, 'discussion_id' => 3, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo</p></t>'],
                ['id' => 10, 'number' => 1, 'discussion_id' => 10, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo</p></t>'],
            ],
            'discussion_user' => [
                ['discussion_id' => 1, 'user_id' => 2, 'last_read_post_number' => 1, 'subscription' => 'follow'],
                ['discussion_id' => 2, 'user_id' => 2, 'last_read_post_number' => 1, 'subscription' => 'ignore'],
                // User 3 (acme) follows discussion 10 — kept separate from user 2's subscriptions
                // so the negation tests (which assert exact ID sets for user 2) are not affected.
                ['discussion_id' => 10, 'user_id' => 3, 'last_read_post_number' => 1, 'subscription' => 'follow'],
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // filter[subscription]=following
    // -------------------------------------------------------------------------

    #[Test]
    public function subscription_filter_following_returns_only_followed_discussions(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['subscription' => 'following']])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');
        $this->assertEqualsCanonicalizing(['1'], $ids);
    }

    #[Test]
    public function subscription_filter_following_returns_nothing_for_guest(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams(['filter' => ['subscription' => 'following']])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');
        $this->assertEmpty($ids);
    }

    // -------------------------------------------------------------------------
    // filter[subscription]=ignoring
    // -------------------------------------------------------------------------

    #[Test]
    public function subscription_filter_ignoring_returns_only_ignored_discussions(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['subscription' => 'ignoring']])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');
        $this->assertEqualsCanonicalizing(['2'], $ids);
    }

    // -------------------------------------------------------------------------
    // Negation
    // -------------------------------------------------------------------------

    #[Test]
    public function subscription_filter_following_negated_excludes_followed_discussions(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['-subscription' => 'following']])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');
        // The followed discussion must be excluded; everything else is included.
        $this->assertNotContains('1', $ids);
        $this->assertContains('2', $ids);
        $this->assertContains('3', $ids);
    }

    #[Test]
    public function subscription_filter_ignoring_negated_excludes_ignored_discussions(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['-subscription' => 'ignoring']])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');
        // The ignored discussion must be excluded; everything else is included.
        $this->assertNotContains('2', $ids);
        $this->assertContains('1', $ids);
        $this->assertContains('3', $ids);
    }

    // -------------------------------------------------------------------------
    // Value variants accepted by SubscriptionFilter's regex
    // -------------------------------------------------------------------------

    public static function followVariantsProvider(): array
    {
        return [
            'following' => ['following'],
            'followed' => ['followed'],
        ];
    }

    #[Test]
    #[DataProvider('followVariantsProvider')]
    public function subscription_filter_accepts_follow_variants(string $value): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['subscription' => $value]])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');
        $this->assertEqualsCanonicalizing(['1'], $ids, "Value '{$value}' should match followed discussions");
    }

    public static function ignoreVariantsProvider(): array
    {
        return [
            'ignoring' => ['ignoring'],
            'ignored' => ['ignored'],
        ];
    }

    #[Test]
    #[DataProvider('ignoreVariantsProvider')]
    public function subscription_filter_accepts_ignore_variants(string $value): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['subscription' => $value]])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');
        $this->assertEqualsCanonicalizing(['2'], $ids, "Value '{$value}' should match ignored discussions");
    }

    // -------------------------------------------------------------------------
    // Unrecognised value is silently ignored (no crash, empty result)
    // -------------------------------------------------------------------------

    #[Test]
    public function subscription_filter_with_unrecognised_value_returns_empty_not_crash(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['subscription' => '跟随']])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');
        $this->assertEmpty($ids, 'An unrecognised subscription value should return no results, not crash');
    }

    // =========================================================================
    // TDD: Intended behaviour after the localization alias fix
    //
    // The JS GambitManager will always send the canonical internal value
    // ('follow' or 'ignore') as the filter value — never the surface keyword.
    // SubscriptionFilter must therefore accept these canonical values directly.
    // Unrecognised values must return 200 with empty results, not 500.
    //
    // All tests in this section will FAIL until SubscriptionFilter is updated.
    // =========================================================================

    // -------------------------------------------------------------------------
    // Canonical internal values ('follow' / 'ignore') accepted directly
    //
    // After the fix, SubscriptionGambit.toFilter() will pass the canonical
    // internal value rather than the surface keyword, so SubscriptionFilter
    // must recognise these.
    // -------------------------------------------------------------------------

    #[Test]
    public function subscription_filter_accepts_canonical_follow_value(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['subscription' => 'follow']])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');
        $this->assertEqualsCanonicalizing(['1'], $ids, "'follow' (canonical) should match followed discussions");
    }

    #[Test]
    public function subscription_filter_accepts_canonical_ignore_value(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['subscription' => 'ignore']])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');
        $this->assertEqualsCanonicalizing(['2'], $ids, "'ignore' (canonical) should match ignored discussions");
    }

    #[Test]
    public function subscription_filter_canonical_follow_negated(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['-subscription' => 'follow']])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');
        $this->assertNotContains('1', $ids);
        $this->assertContains('2', $ids);
        $this->assertContains('3', $ids);
    }

    #[Test]
    public function subscription_filter_canonical_ignore_negated(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['-subscription' => 'ignore']])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');
        $this->assertNotContains('2', $ids);
        $this->assertContains('1', $ids);
        $this->assertContains('3', $ids);
    }

    // -------------------------------------------------------------------------
    // Unrecognised value: 200 + empty (no crash)
    //
    // Once the null-check is added, any value that doesn't match a known
    // subscription type must be silently ignored and return no results.
    // -------------------------------------------------------------------------

    #[Test]
    public function subscription_filter_with_unrecognised_value_returns_empty(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['subscription' => '跟随']])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');
        $this->assertEmpty($ids, 'An unrecognised subscription value should return no results, not crash');
    }

    #[Test]
    public function subscription_filter_with_empty_value_returns_empty(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['subscription' => '']])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');
        $this->assertEmpty($ids, 'An empty subscription value should return no results, not crash');
    }

    // -------------------------------------------------------------------------
    // Subscription extender — third-party type registration
    // -------------------------------------------------------------------------

    #[Test]
    public function subscription_extender_registers_additional_alias_for_existing_type(): void
    {
        // Register a third-party alias ('subscribed') that maps to the built-in 'follow' canonical.
        // User 3 (acme) follows discussion 10 — seeded in setUp().
        $this->extend(
            (new Subscription())
                ->addSubscriptionType('follow', ['subscribed'])
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 3])
                ->withQueryParams(['filter' => ['subscription' => 'subscribed']])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');
        $this->assertContains('10', $ids, "'subscribed' alias should resolve to 'follow' and match discussion 10");
    }

    #[Test]
    public function subscription_extender_unregistered_alias_returns_empty(): void
    {
        // Without registering 'subscribed', it should return no results.
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 3])
                ->withQueryParams(['filter' => ['subscription' => 'subscribed']])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');
        $this->assertEmpty($ids, 'Unregistered alias should return no results');
    }
}
