<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Tests\integration\api;

use Flarum\PackageManager\Tests\integration\RefreshComposerSetup;
use Flarum\PackageManager\Tests\integration\TestCase;

class GlobalUpdateTest extends TestCase
{
    use RefreshComposerSetup;

    /**
     * @test
     */
    public function can_global_update()
    {
        $response = $this->send(
            $this->request('POST', '/api/package-manager/global-update', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());
    }
}
