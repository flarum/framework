<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\integration\api\extensions;

use Flarum\ExtensionManager\Tests\integration\RefreshComposerSetup;
use Flarum\ExtensionManager\Tests\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RequireExtensionTest extends TestCase
{
    use RefreshComposerSetup;

    #[Test]
    public function extension_uninstalled_by_default()
    {
        $this->assertExtensionNotExists('v17development-blog');
    }

    #[Test]
    public function requiring_an_existing_extension_fails()
    {
        $response = $this->send(
            $this->request('POST', '/api/extension-manager/extensions', [
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

    #[Test]
    public function requiring_a_compatible_extension_works()
    {
        $response = $this->send(
            $this->request('POST', '/api/extension-manager/extensions', [
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

    #[Test]
    public function requiring_a_compatible_extension_with_specific_version_works()
    {
        $response = $this->send(
            $this->request('POST', '/api/extension-manager/extensions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'package' => 'v17development/flarum-blog:0.4.0'
                    ]
                ]
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertExtensionExists('v17development-blog');
    }

    #[Test]
    public function requiring_an_uncompatible_extension_fails()
    {
        $response = $this->send(
            $this->request('POST', '/api/extension-manager/extensions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'package' => 'flarum/auth-github'
                    ]
                ]
            ])
        );

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals('extension_incompatible_with_instance', $this->errorDetails($response)['guessed_cause']);
    }

    #[Test]
    public function requiring_an_uncompatible_extension_with_specific_version_fails()
    {
        $response = $this->send(
            $this->request('POST', '/api/extension-manager/extensions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'package' => 'flarum/auth-github:0.1.0-beta.9'
                    ]
                ]
            ])
        );

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals('extension_incompatible_with_instance', $this->errorDetails($response)['guessed_cause']);
    }
}
