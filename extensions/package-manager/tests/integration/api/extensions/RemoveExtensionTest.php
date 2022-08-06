<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Tests\integration\api\extensions;

use Flarum\PackageManager\Tests\integration\RefreshComposerSetup;
use Flarum\PackageManager\Tests\integration\TestCase;

class RemoveExtensionTest extends TestCase
{
    use RefreshComposerSetup;

    /**
     * @test
     */
    public function extension_installed_by_default()
    {
        $this->assertExtensionExists('flarum-tags');
    }

    /**
     * @test
     */
    public function removing_an_extension_works()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/package-manager/extensions/flarum-tags', [
                'authenticatedAs' => 1
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertExtensionNotExists('flarum-tags');
    }

    /**
     * @test
     */
    public function removing_a_non_existant_extension_fails()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/package-manager/extensions/flarum-potato', [
                'authenticatedAs' => 1
            ])
        );

        $this->assertEquals(409, $response->getStatusCode());
    }
}
