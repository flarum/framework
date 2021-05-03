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
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class ModelUrlTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $userClass = User::class;

        $this->setting("slug_driver_$userClass", 'testDriver');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ]
        ]);
    }

    /**
     * @test
     */
    public function uses_default_driver_by_default()
    {
        $slugManager = $this->app()->getContainer()->make(SlugManager::class);

        $testUser = User::find(1);

        $this->assertEquals('admin', $slugManager->forResource(User::class)->toSlug($testUser));
        $this->assertEquals('1', $slugManager->forResource(User::class)->fromSlug('admin', $testUser)->id);
    }

    /**
     * @test
     */
    public function custom_slug_driver_has_effect_if_added()
    {
        $this->extend((new Extend\ModelUrl(User::class))->addSlugDriver('testDriver', TestSlugDriver::class));

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
