<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\frontend;

use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AssetsRevisionPayloadTest extends TestCase
{
    private function bootPayload(): array
    {
        $html = $this->send($this->request('GET', '/'))->getBody()->getContents();

        // The client boots from the JSON in the #flarum-json-payload script tag.
        preg_match('/<script id="flarum-json-payload"[^>]*>(.*?)<\/script>/s', $html, $m);
        $this->assertNotEmpty($m, 'Could not find the flarum-json-payload script.');

        return json_decode(html_entity_decode($m[1], ENT_QUOTES), true);
    }

    #[Test]
    public function boot_payload_includes_the_assets_revision()
    {
        $payload = $this->bootPayload();

        $this->assertArrayHasKey('assetsRevision', $payload);
        $this->assertNotEmpty($payload['assetsRevision']);
    }
}
