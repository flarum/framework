<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\queue;

use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use PHPUnit\Framework\Attributes\Test;

/**
 * The cache factory core binds as `cache`. Laravel code asks it for a store
 * by name (the Cache facade's `store()`, `#[Cache]` injection, queue
 * internals), and Flarum has only the one.
 */
class CacheFactoryTest extends TestCase
{
    #[Test]
    public function the_default_store_is_the_cache_store(): void
    {
        $container = $this->app()->getContainer();

        $this->assertSame($container->make(Repository::class), $container->make(Factory::class)->store());
    }

    #[Test]
    public function a_named_store_is_the_cache_store(): void
    {
        $container = $this->app()->getContainer();

        $this->assertSame($container->make(Repository::class), $container->make('cache')->store('file'));
    }

    #[Test]
    public function the_store_reads_and_writes_the_shared_cache(): void
    {
        $container = $this->app()->getContainer();

        $container->make(Factory::class)->store()->put('cache-factory-test', 'stored', 60);

        $this->assertSame('stored', $container->make(Repository::class)->get('cache-factory-test'));
    }
}
