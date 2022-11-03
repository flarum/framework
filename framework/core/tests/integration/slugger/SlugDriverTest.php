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
use Flarum\Http\SlugManager;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class SlugDriverTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
            'discussions' => [
                ['id' => 20, 'title' => 'Empty discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 0],
                ['id' => 21, 'title' => 'తెలుగు', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 0],
                ['id' => 22, 'title' => '支持中文吗', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 0],
                ['id' => 23, 'title' => 'తెలుగు%$', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 0],
                ['id' => 24, 'title' => '支持中文吗%*', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => null, 'comment_count' => 0, 'is_private' => 0],
            ],
        ]);
    }

    /**
     * @dataProvider slugInstancePairDataProvider
     * @test
     */
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

    /**
     * @dataProvider slugInstancePairDataProvider
     * @test
     */
    public function slugger_returns_the_correct_instance_from_slug(string $driver, string $modelClassName, int $id, string $slug)
    {
        $this->setting("slug_driver_$modelClassName", $driver);

        /** @var SlugManager $slugger */
        $slugger = $this->app()->getContainer()->make(SlugManager::class)->forResource($modelClassName);

        $this->assertEquals($modelClassName::query()->find($id), $slugger->fromSlug($slug, User::query()->find(1)));
    }

    public function slugInstancePairDataProvider(): array
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
        ];
    }
}
