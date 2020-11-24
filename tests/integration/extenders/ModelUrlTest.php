<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Database\AbstractModel;
use Flarum\Extend;
use Flarum\Http\SlugDriverInterface;
use Flarum\Http\SlugManager;
use Flarum\Http\UrlGenerator;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Flarum\User\User;

class ModelUrlTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function prepDb()
    {
        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
                $this->normalUser(),
            ],
        ]);
    }

    protected function activateCustomDriver()
    {
        $userClass = User::class;
        $this->prepareDatabase([
            'settings' => [
                ['key' => "slug_driver_$userClass", 'value' => 'testDriver'],
            ],
        ]);
    }

//    public function tearDown()
//    {
//        $userClass = User::class;
//        $this->prepareDatabase([
//            'settings' => [
//                ['key' => "slug_driver_$userClass", 'value' => 'default'],
//            ],
//        ]);
//        parent::tearDown();
//    }

    /**
     * @test
     */
    public function default_url_generator_used_by_default()
    {
        $this->prepDb();

        $urlGenerator = $this->app()->getContainer()->make(UrlGenerator::class);

        $testUser = User::find(1);

        $this->assertEquals($urlGenerator->to('forum')->route('user', ['username' => 'admin']), $urlGenerator->toResource(User::class, $testUser));
    }

    /**
     * @test
     */
    public function custom_url_generator_can_be_used()
    {
        $this->extend(
            (new Extend\ModelUrl(User::class))->setUrlGenerator(function (UrlGenerator $urlGenerator, User $instance) {
                return 'hello there!';
            })
        );

        $this->prepDb();

        $urlGenerator = $this->app()->getContainer()->make(UrlGenerator::class);

        $testUser = User::find(1);

        $this->assertEquals('hello there!', $urlGenerator->toResource(User::class, $testUser));
    }

    public function uses_default_driver_by_default()
    {
        $this->prepDb();

        $slugManager = $this->app()->getContainer()->make(SlugManager::class);

        $testUser = User::find(1);

        $this->assertEquals('admin', $slugManager->forResource(User::class)->toSlug($testUser));
        $this->assertEquals('1', $slugManager->forResource(User::class)->fromSlug('admin', $testUser)->id);
    }

    /**
     * @test
     */
    public function custom_slug_driver_doesnt_have_effect_unless_enabled()
    {
        $this->extend((new Extend\ModelUrl(User::class))->addSlugDriver('testDriver', TestSlugDriver::class));

        $this->prepDb();

        $slugManager = $this->app()->getContainer()->make(SlugManager::class);

        $testUser = User::find(1);

        $this->assertEquals('admin', $slugManager->forResource(User::class)->toSlug($testUser));
        $this->assertEquals('1', $slugManager->forResource(User::class)->fromSlug('admin', $testUser)->id);
    }

    /**
     * @test
     */
    public function custom_slug_driver_has_effect_if_enabled()
    {
        $this->extend((new Extend\ModelUrl(User::class))->addSlugDriver('testDriver', TestSlugDriver::class));

        $this->prepDb();
        $this->activateCustomDriver();

        $slugManager = $this->app()->getContainer()->make(SlugManager::class);

        $testUser = User::find(1);

        $this->assertEquals('test-slug', $slugManager->forResource(User::class)->toSlug($testUser));
        $this->assertEquals('1', $slugManager->forResource(User::class)->fromSlug('random-gibberish', $testUser)->id);
    }
}

class TestSlugDriver implements SlugDriverInterface
{
    public function toSlug(AbstractModel $instance): string
    {
        return 'test-slug';
    }

    public function fromSlug(string $slug, User $actor): AbstractModel
    {
        return User::find(1);
    }
}
