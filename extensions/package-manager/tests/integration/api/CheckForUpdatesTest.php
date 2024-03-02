<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\integration\api;

use Flarum\ExtensionManager\Tests\integration\ChangeComposerConfig;
use Flarum\ExtensionManager\Tests\integration\RefreshComposerSetup;
use Flarum\ExtensionManager\Tests\integration\TestCase;
use Illuminate\Support\Arr;

class CheckForUpdatesTest extends TestCase
{
    use RefreshComposerSetup;
    use ChangeComposerConfig;

    /**
     * @test
     */
    public function can_check_for_updates()
    {
        $this->setComposerConfig([
            'require' => [
                'flarum/core' => '^1.0.0',
                'flarum/tags' => '1.0.0',
            ]
        ]);

        $response = $this->send(
            $this->request('POST', '/api/extension-manager/check-for-updates', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['flarum/tags'], Arr::pluck(json_decode((string) $response->getBody(), true)['updates']['installed'], 'name'));
    }
}
