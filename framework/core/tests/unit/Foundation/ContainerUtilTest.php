<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Foundation;

use Flarum\Foundation\ContainerUtil;
use Flarum\Testing\unit\TestCase;
use Illuminate\Container\Container;

class ContainerUtilTest extends TestCase
{
    private $container;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
    }

    /** @test */
    public function it_works_with_closures()
    {
        $callback = ContainerUtil::wrapCallback(function ($array) {
            $array['key'] = 'newValue';

            return 'return';
        }, $this->container);

        $array = ['key' => 'value'];
        $return = $callback($array);

        $this->assertEquals('value', $array['key']);
        $this->assertEquals('return', $return);
    }

    /** @test */
    public function it_works_with_invokable_classes()
    {
        $callback = ContainerUtil::wrapCallback(CustomInvokableClass::class, $this->container);

        $array = ['key' => 'value2'];
        $return = $callback($array);

        $this->assertEquals('value2', $array['key']);
        $this->assertEquals('return2', $return);
    }

    /** @test */
    public function it_works_with_invokable_objects()
    {
        $callback = ContainerUtil::wrapCallback(new class {
            public function __invoke($array)
            {
                $array['key'] = 'newValue5';

                return 'return5';
            }
        }, $this->container);

        $array = ['key' => 'value5'];
        $return = $callback($array);

        $this->assertEquals('value5', $array['key']);
        $this->assertEquals('return5', $return);
    }

    /** @test */
    public function it_works_with_global_functions()
    {
        $callback = ContainerUtil::wrapCallback('boolval', $this->container);

        $this->assertEquals(true, $callback(true));
        $this->assertEquals(true, $callback(1));
        $this->assertEquals(true, $callback('1'));
        $this->assertEquals(false, $callback(0));
        $this->assertEquals(false, $callback(false));
    }

    /** @test */
    public function it_works_with_static_class_method_arrays()
    {
        $callback = ContainerUtil::wrapCallback([ClassWithMethod::class, 'staticMethod'], $this->container);

        $this->assertEquals('returnStatic', $callback());
    }

    /** @test */
    public function it_allows_passing_args_by_reference_on_closures()
    {
        $callback = ContainerUtil::wrapCallback(function (&$array) {
            $array['key'] = 'newValue3';

            return 'return3';
        }, $this->container);

        $array = ['key' => 'value3'];
        $return = $callback($array);

        $this->assertEquals('newValue3', $array['key']);
        $this->assertEquals('return3', $return);
    }

    /** @test */
    public function it_allows_passing_args_by_reference_on_invokable_classes()
    {
        $callback = ContainerUtil::wrapCallback(SecondCustomInvokableClass::class, $this->container);

        $array = ['key' => 'value4'];
        $return = $callback($array);

        $this->assertEquals('newValue4', $array['key']);
        $this->assertEquals('return4', $return);
    }

    /** @test */
    public function it_allows_passing_args_by_reference_on_invokable_objects()
    {
        $callback = ContainerUtil::wrapCallback(new class {
            public function __invoke(&$array)
            {
                $array['key'] = 'newValue6';

                return 'return6';
            }
        }, $this->container);

        $array = ['key' => 'value6'];
        $return = $callback($array);

        $this->assertEquals('newValue6', $array['key']);
        $this->assertEquals('return6', $return);
    }
}

class CustomInvokableClass
{
    public function __invoke($array)
    {
        $array['key'] = 'newValue2';

        return 'return2';
    }
}

class SecondCustomInvokableClass
{
    public function __invoke(&$array)
    {
        $array['key'] = 'newValue4';

        return 'return4';
    }
}

class ClassWithMethod
{
    public static function staticMethod()
    {
        return 'returnStatic';
    }
}
