<?php

namespace Flarum\Tests\integration\middleware;

use Flarum\Testing\integration\TestCase;

class ReferrerPolicyTest extends TestCase {
    /**
     * @test
     */
    public function has_referer_header() {
        $response = $this->send(
            $this->request('GET', '/')
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('Referrer-Policy', $response->getHeaders());
    }

    /**
     * @test
     */
    public function has_default_referer_policy() {
        $response = $this->send(
            $this->request('GET', '/')
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('same-origin', $response->getHeader('Referrer-Policy')[0]);
    }
}
