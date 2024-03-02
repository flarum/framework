<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\integration\api;

use Flarum\ExtensionManager\Tests\integration\RefreshComposerSetup;
use Flarum\ExtensionManager\Tests\integration\TestCase;

class GlobalUpdateTest extends TestCase
{
    use RefreshComposerSetup;

    /**
     * @test
     */
    public function can_global_update()
    {
        $response = $this->send(
            $this->request('POST', '/api/extension-manager/global-update', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());
    }
}
