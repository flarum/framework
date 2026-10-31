<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Api\Middleware;

use Flarum\Api\Middleware\AddAssetsRevisionHeader;
use Flarum\Frontend\Compiler\AssetsRevision;
use Flarum\Testing\unit\TestCase;
use Illuminate\Contracts\Container\Container;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Server\RequestHandlerInterface;

class AddAssetsRevisionHeaderTest extends TestCase
{
    /**
     * A long-lived tab only ever makes API requests, so this middleware is its
     * sole line to the asset revision. If the forum assets were flagged dirty
     * (e.g. by an extension toggle) they must be rebuilt HERE, before the token
     * is stamped — otherwise the token is computed from the not-yet-rebuilt
     * manifest and never moves for API-only clients: no reload prompt until
     * some unrelated visitor happens to load a full page.
     */
    #[Test]
    public function rebuilds_dirty_assets_before_stamping_the_token()
    {
        $order = [];

        $revision = m::mock(AssetsRevision::class);
        $revision->shouldReceive('token')->once()->andReturnUsing(function () use (&$order) {
            $order[] = 'token';

            return 'abc123';
        });

        $middleware = m::mock(
            AddAssetsRevisionHeader::class.'[recompileDirtyAssets]',
            [m::mock(Container::class), $revision]
        )->shouldAllowMockingProtectedMethods();

        $middleware->shouldReceive('recompileDirtyAssets')->once()->andReturnUsing(function () use (&$order) {
            $order[] = 'rebuild';
        });

        $handler = m::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('handle')->andReturn(new Response());

        $response = $middleware->process(new ServerRequest(), $handler);

        $this->assertSame(['rebuild', 'token'], $order, 'dirty assets must be rebuilt before the token is read');
        $this->assertSame('abc123', $response->getHeaderLine(AddAssetsRevisionHeader::HEADER_NAME));
    }
}
