<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Database;

use Flarum\Database\AbstractModel;
use Flarum\Testing\unit\TestCase;

class AbstractModelCastsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        AbstractModel::$customCasts = [];
        AbstractModel::flushCastsCache();
    }

    protected function tearDown(): void
    {
        AbstractModel::$customCasts = [];
        AbstractModel::flushCastsCache();

        parent::tearDown();
    }

    public function test_it_includes_casts_declared_on_the_model(): void
    {
        $model = new AbstractModelCastsTestModel;

        $this->assertSame('bool', $model->getCasts()['is_native'] ?? null);
    }

    public function test_it_includes_custom_casts_registered_for_the_model(): void
    {
        AbstractModel::$customCasts[AbstractModelCastsTestModel::class] = ['extended' => 'datetime'];

        $model = new AbstractModelCastsTestModel;

        $this->assertSame('datetime', $model->getCasts()['extended'] ?? null);
    }

    public function test_it_inherits_custom_casts_registered_against_a_parent_class(): void
    {
        AbstractModel::$customCasts[AbstractModelCastsTestModel::class] = ['from_parent' => 'int'];

        $model = new AbstractModelCastsTestChildModel;

        $this->assertSame('int', $model->getCasts()['from_parent'] ?? null);
    }

    public function test_casts_registered_on_a_child_override_the_parent(): void
    {
        AbstractModel::$customCasts[AbstractModelCastsTestModel::class] = ['overridden' => 'int'];
        AbstractModel::$customCasts[AbstractModelCastsTestChildModel::class] = ['overridden' => 'bool'];

        $model = new AbstractModelCastsTestChildModel;

        $this->assertSame('bool', $model->getCasts()['overridden'] ?? null);
    }

    public function test_two_instances_of_the_same_model_resolve_the_same_casts(): void
    {
        AbstractModel::$customCasts[AbstractModelCastsTestModel::class] = ['shared' => 'bool'];

        $this->assertEquals(
            (new AbstractModelCastsTestModel)->getCasts(),
            (new AbstractModelCastsTestModel)->getCasts()
        );
    }

    public function test_sibling_models_do_not_share_each_others_casts(): void
    {
        // A per-class cache keyed carelessly (or not keyed at all) would leak
        // the first model's casts onto the second.
        AbstractModel::$customCasts[AbstractModelCastsTestModel::class] = ['only_on_parent' => 'bool'];
        AbstractModel::$customCasts[AbstractModelCastsTestSiblingModel::class] = ['only_on_sibling' => 'int'];

        (new AbstractModelCastsTestModel)->getCasts();

        $sibling = (new AbstractModelCastsTestSiblingModel)->getCasts();

        $this->assertArrayNotHasKey('only_on_parent', $sibling);
        $this->assertSame('int', $sibling['only_on_sibling'] ?? null);
    }

    public function test_casts_registered_after_a_first_resolution_are_picked_up(): void
    {
        // Extenders mutate $customCasts during boot, which can happen after a
        // model has already resolved its casts once. A cache that never
        // invalidates would silently ignore the newly registered cast.
        $this->assertArrayNotHasKey('late', (new AbstractModelCastsTestModel)->getCasts());

        AbstractModel::$customCasts[AbstractModelCastsTestModel::class] = ['late' => 'bool'];
        AbstractModel::flushCastsCache();

        $this->assertSame('bool', (new AbstractModelCastsTestModel)->getCasts()['late'] ?? null);
    }
}

class AbstractModelCastsTestModel extends AbstractModel
{
    protected $casts = ['is_native' => 'bool'];
}

class AbstractModelCastsTestChildModel extends AbstractModelCastsTestModel
{
}

class AbstractModelCastsTestSiblingModel extends AbstractModel
{
}
