<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace integration\slugger;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Http\SlugDriverInterface;
use Flarum\Http\SlugManager;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class SlugDriverTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 20, 'title' => 'Empty discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 0],
                ['id' => 21, 'title' => 'తెలుగు', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 0],
                ['id' => 22, 'title' => '支持中文吗', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 0],
                ['id' => 23, 'title' => 'తెలుగు%$', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 0],
                ['id' => 24, 'title' => '支持中文吗%*', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 0],
            ],
        ]);
    }

    #[Test]
    #[DataProvider('slugInstancePairDataProvider')]
    public function slugger_formats_the_correct_slug_from_instance(string $driver, string $modelClassName, int $id, string $slug)
    {
        $this->setting("slug_driver_$modelClassName", $driver);

        /** @var SlugManager $slugger */
        $slugger = $this->app()->getContainer()->make(SlugManager::class)->forResource($modelClassName);

        $instance = $modelClassName::query()->find($id);

        /** @see Discussion::setTitleAttribute() */
        if ($modelClassName === Discussion::class) {
            $instance->title = $instance->title;
        }

        $this->assertEquals($slug, $slugger->toSlug($instance));
    }

    #[Test]
    #[DataProvider('slugInstancePairDataProvider')]
    public function slugger_returns_the_correct_instance_from_slug(string $driver, string $modelClassName, int $id, string $slug)
    {
        $this->setting("slug_driver_$modelClassName", $driver);

        /** @var SlugManager $slugger */
        $slugger = $this->app()->getContainer()->make(SlugManager::class)->forResource($modelClassName);

        $this->assertEquals($modelClassName::query()->find($id), $slugger->fromSlug($slug, User::query()->find(1)));
    }

    #[Test]
    #[DataProvider('fromUserSlugVariantsDataProvider')]
    public function id_with_display_name_driver_resolves_user_from_slug_variants(string $slug, int $expectedId)
    {
        $this->setting('slug_driver_'.User::class, 'id_with_display_name');

        /** @var SlugDriverInterface $slugger */
        $slugger = $this->app()->getContainer()->make(SlugManager::class)->forResource(User::class);

        $this->assertEquals(User::query()->find($expectedId), $slugger->fromSlug($slug, User::query()->find(1)));
    }

    #[Test]
    #[DataProvider('notAnIdDataProvider')]
    public function id_based_drivers_find_nothing_for_a_slug_that_is_not_an_id(string $driver, string $modelClassName, string $slug)
    {
        $this->setting("slug_driver_$modelClassName", $driver);

        /** @var SlugDriverInterface $slugger */
        $slugger = $this->app()->getContainer()->make(SlugManager::class)->forResource($modelClassName);

        // Not a database error either: PostgreSQL refuses text compared with an integer key.
        $this->expectException(ModelNotFoundException::class);

        $slugger->fromSlug($slug, User::query()->find(1));
    }

    public static function notAnIdDataProvider(): array
    {
        $cases = [];

        // Each built from an id that exists: MySQL reads "20abc" as 20.
        foreach ([['default', Discussion::class, 20], ['utf8', Discussion::class, 20], ['id', User::class, 2], ['id_with_display_name', User::class, 2]] as [$driver, $model, $id]) {
            foreach (['nobody', "{$id}abc", "$id.0", "-$id", ''] as $slug) {
                $cases["$driver ".class_basename($model)." '$slug'"] = [$driver, $model, $slug];
            }
        }

        return $cases;
    }

    #[Test]
    public function a_profile_url_that_is_not_an_id_is_not_found()
    {
        $this->setting('slug_driver_'.User::class, 'id');

        $this->assertEquals(200, $this->send($this->request('GET', '/u/2'))->getStatusCode());
        $this->assertEquals(404, $this->send($this->request('GET', '/u/2abc'))->getStatusCode());
        $this->assertEquals(404, $this->send($this->request('GET', '/u/nobody'))->getStatusCode());
    }

    #[Test]
    public function an_author_filter_that_is_not_an_id_matches_nobody()
    {
        $this->setting('slug_driver_'.User::class, 'id');

        $response = $this->send($this->request('GET', '/api/discussions', ['authenticatedAs' => 1])->withQueryParams(['filter' => ['author' => '2abc']]));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame([], json_decode((string) $response->getBody(), true)['data']);
    }

    public static function fromUserSlugVariantsDataProvider(): array
    {
        return [
            'canonical slug' => ['2-normal', 2],
            'bare ID' => ['2', 2],
            'wrong display name' => ['2-wrong-slug', 2],
        ];
    }

    public static function slugInstancePairDataProvider(): array
    {
        return [
            ['default', Discussion::class, 20, '20-empty-discussion'],
            ['default', Discussion::class, 21, '21'],
            ['default', Discussion::class, 22, '22'],

            ['utf8', Discussion::class, 20, '20-empty-discussion'],
            ['utf8', Discussion::class, 21, '21-తెలుగు'],
            ['utf8', Discussion::class, 22, '22-支持中文吗'],
            ['utf8', Discussion::class, 23, '23-తెలుగు'],
            ['utf8', Discussion::class, 24, '24-支持中文吗'],

            ['default', User::class, 2, 'normal'],
            ['id', User::class, 2, '2'],
            ['id_with_display_name', User::class, 2, '2-normal'],
        ];
    }
}
