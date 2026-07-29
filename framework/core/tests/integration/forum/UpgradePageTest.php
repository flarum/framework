<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Foundation\Application;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UpgradePageTest extends TestCase
{
    #[Test]
    public function upgrade_page_returns_503_when_version_is_outdated(): void
    {
        $this->setting('version', '0.1.0');

        $response = $this->send(
            $this->request('GET', '/')
        );

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertStringContainsString('Update Flarum', (string) $response->getBody());
    }

    #[Test]
    public function upgrade_page_is_not_shown_when_version_matches(): void
    {
        $this->setting('version', Application::VERSION);

        $response = $this->send(
            $this->request('GET', '/')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringNotContainsString('Update Flarum', (string) $response->getBody());
    }
}
