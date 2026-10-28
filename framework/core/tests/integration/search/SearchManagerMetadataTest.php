<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\search;

use Flarum\Extend;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchManager;
use Flarum\Search\SearchState;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Search\Filter\EmailFilter;
use Flarum\User\Search\Filter\GroupFilter;
use Flarum\User\Search\UserSearcher;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

class SearchManagerMetadataTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    private function manager(): SearchManager
    {
        return $this->app()->getContainer()->make(SearchManager::class);
    }

    private function admin(): User
    {
        $this->app();

        return User::findOrFail(1);
    }

    private function member(): User
    {
        $this->app();

        return User::findOrFail(2);
    }

    #[Test]
    public function filters_are_exposed_by_key()
    {
        $filters = $this->manager()->filters(User::class);

        $this->assertArrayHasKey('email', $filters);
        $this->assertArrayHasKey('group', $filters);
    }

    #[Test]
    public function filters_are_empty_for_non_searchable_resources()
    {
        $this->assertSame([], $this->manager()->filters(stdClass::class));
        $this->assertFalse($this->manager()->fulltext(stdClass::class));
    }

    #[Test]
    public function filters_describe_themselves()
    {
        $this->assertSame(['kind' => 'string'], (new EmailFilter)->describe($this->admin()));
        $this->assertSame(['kind' => 'relationship', 'related' => 'groups'], (new GroupFilter)->describe($this->admin()));
    }

    #[Test]
    public function the_email_filter_is_hidden_without_edit_credentials()
    {
        // Mirrors `EmailFilter::filter()`, which ignores the value without `user.editCredentials`.
        $this->assertNull((new EmailFilter)->describe($this->member()));
        $this->assertSame(['kind' => 'relationship', 'related' => 'groups'], (new GroupFilter)->describe($this->member()));
    }

    #[Test]
    public function described_filters_are_scoped_to_the_actor()
    {
        $admin = $this->manager()->describeFilters(User::class, $this->admin());
        $member = $this->manager()->describeFilters(User::class, $this->member());

        $this->assertArrayHasKey('email', $admin);
        $this->assertArrayNotHasKey('email', $member);
        $this->assertArrayHasKey('group', $member);
    }

    #[Test]
    public function a_callable_extender_description_receives_the_actor()
    {
        $this->extend(
            (new Extend\SearchDriver(DatabaseSearchDriver::class))
                ->addFilter(UserSearcher::class, CustomMetadataFilter::class)
                ->describeFilter(UserSearcher::class, 'customMetadata', fn (User $actor) => $actor->isAdmin() ? ['kind' => 'boolean'] : null)
        );

        $this->assertSame(['kind' => 'boolean'], $this->manager()->describeFilters(User::class, $this->admin())['customMetadata']);
        $this->assertArrayNotHasKey('customMetadata', $this->manager()->describeFilters(User::class, $this->member()));
    }

    #[Test]
    public function search_is_described_in_one_pass()
    {
        $search = $this->manager()->describeSearch(User::class, $this->admin());

        $this->assertTrue($search['fulltext']);
        $this->assertSame($this->manager()->describeFilters(User::class, $this->admin()), $search['filters']);
        $this->assertSame(['fulltext' => false, 'filters' => []], $this->manager()->describeSearch(stdClass::class, $this->admin()));
    }

    #[Test]
    public function a_resource_searched_by_another_driver_is_not_described()
    {
        $this->extend((new Extend\SearchDriver(ExternalSearchDriver::class))->addSearcher(User::class, UserSearcher::class));
        $this->setting('search_driver_'.User::class, ExternalSearchDriver::name());

        $this->assertSame(['fulltext' => false, 'filters' => []], $this->manager()->describeSearch(User::class, $this->admin()));
        $this->assertSame([], $this->manager()->filters(User::class));
        $this->assertFalse($this->manager()->fulltext(User::class));
    }

    #[Test]
    public function users_support_fulltext()
    {
        $this->assertTrue($this->manager()->fulltext(User::class));
    }

    #[Test]
    public function custom_filters_appear()
    {
        $this->extend(
            (new Extend\SearchDriver(DatabaseSearchDriver::class))
                ->addFilter(UserSearcher::class, CustomMetadataFilter::class)
        );

        $this->assertArrayHasKey('customMetadata', $this->manager()->filters(User::class));
    }

    #[Test]
    public function filters_describe_through_the_interface()
    {
        $described = $this->manager()->describeFilters(User::class, $this->admin());

        $this->assertSame(['kind' => 'string'], $described['email']);
        $this->assertSame(['kind' => 'relationship', 'related' => 'groups'], $described['group']);
    }

    #[Test]
    public function filters_without_a_description_are_left_out()
    {
        $this->extend(
            (new Extend\SearchDriver(DatabaseSearchDriver::class))
                ->addFilter(UserSearcher::class, CustomMetadataFilter::class)
        );

        $this->assertArrayNotHasKey('customMetadata', $this->manager()->describeFilters(User::class, $this->admin()));
    }

    #[Test]
    public function an_extender_can_describe_a_filter_it_does_not_own()
    {
        $this->extend(
            (new Extend\SearchDriver(DatabaseSearchDriver::class))
                ->addFilter(UserSearcher::class, CustomMetadataFilter::class)
                ->describeFilter(UserSearcher::class, 'customMetadata', ['kind' => 'boolean'])
        );

        $this->assertSame(['kind' => 'boolean'], $this->manager()->describeFilters(User::class, $this->admin())['customMetadata']);
    }

    #[Test]
    public function an_extender_description_wins_over_the_interface()
    {
        $this->extend(
            (new Extend\SearchDriver(DatabaseSearchDriver::class))
                ->describeFilter(UserSearcher::class, 'email', fn () => ['kind' => 'string', 'format' => 'email'])
        );

        $this->assertSame(
            ['kind' => 'string', 'format' => 'email'],
            $this->manager()->describeFilters(User::class, $this->admin())['email']
        );
    }

    #[Test]
    public function a_description_for_an_unregistered_key_is_ignored()
    {
        $this->extend(
            (new Extend\SearchDriver(DatabaseSearchDriver::class))
                ->describeFilter(UserSearcher::class, 'nope', ['kind' => 'string'])
        );

        $this->assertArrayNotHasKey('nope', $this->manager()->describeFilters(User::class, $this->admin()));
    }
}

class CustomMetadataFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'customMetadata';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
    }
}

class ExternalSearchDriver extends \Flarum\Search\AbstractDriver
{
    public static function name(): string
    {
        return 'external';
    }
}

