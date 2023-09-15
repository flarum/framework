<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\middleware;

use Flarum\Testing\integration\TestCase;

class ReferrerPolicyTest extends TestCase
{
    /**
     * @test
     */
    public function has_referer_header()
    {
        $response = $this->send(
            $this->request('GET', '/')
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($response->headers->get('Referrer-Policy'));
    }

    /**
     * @test
     */
    public function has_default_referer_policy()
    {
        $response = $this->send(
            $this->request('GET', '/')
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('same-origin', $response->headers->get('Referrer-Policy'));
    }
}
