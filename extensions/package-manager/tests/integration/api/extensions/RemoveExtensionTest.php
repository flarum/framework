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

class RemoveExtensionTest extends TestCase
{
    use RefreshComposerSetup;

    #[Test]
    public function extension_installed_by_default()
    {
        $this->assertExtensionExists('flarum-tags');
    }

    #[Test]
    public function removing_an_extension_works()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/extension-manager/extensions/flarum-tags', [
                'authenticatedAs' => 1
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertExtensionNotExists('flarum-tags');
    }

    #[Test]
    public function removing_a_non_existant_extension_fails()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/extension-manager/extensions/flarum-potato', [
                'authenticatedAs' => 1
            ])
        );

        $this->assertEquals(409, $response->getStatusCode());
    }
}
