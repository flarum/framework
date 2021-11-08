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

class RequireExtensionTest extends TestCase
{
    use RefreshComposerSetup;

    /**
     * @test
     */
    public function extension_uninstalled_by_default()
    {
        $this->assertExtensionNotExists('v17development-blog');
    }

    /**
     * @test
     */
    public function requiring_an_existing_extension_fails()
    {
        $response = $this->send(
            $this->request('POST', '/api/package-manager/extensions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'package' => 'flarum/tags'
                    ]
                ]
            ])
        );

        $this->assertEquals(409, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function requiring_a_compatible_extension_works()
    {
        $response = $this->send(
            $this->request('POST', '/api/package-manager/extensions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'package' => 'v17development/flarum-blog'
                    ]
                ]
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertExtensionExists('v17development-blog');
    }

    /**
     * @test
     */
    public function requiring_an_uncompatible_extension_fails()
    {
        $response = $this->send(
            $this->request('POST', '/api/package-manager/extensions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'package' => 'flarum/auth-github'
                    ]
                ]
            ])
        );

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals('extension_incompatible_with_instance', $this->guessedCause($response));
    }
}
