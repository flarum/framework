<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\integration\api;

use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Refusals reach the browser through Flarum's error handling, with a detail
 * for the admin to read. (The test harness loads only core's translations, so
 * the detail here is the translation key; the unit tests check the English.)
 */
class ErrorsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-extension-manager');
    }

    #[Test]
    public function invalid_input_is_a_validation_error(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/extension-manager/config', ['authenticatedAs' => 1, 'json' => ['action' => 'nonsense']])
        );

        $this->assertEquals(422, $response->getStatusCode());
        $error = json_decode((string) $response->getBody(), true)['errors'][0];
        $this->assertSame('validation_error', $error['code']);
        $this->assertStringEndsWith('unknown_action', $error['detail']);
        $this->assertSame('/data/attributes/action', $error['source']['pointer']);
    }

    #[Test]
    public function a_value_that_cannot_be_used_is_refused_with_a_reason(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/extension-manager/config', ['authenticatedAs' => 1, 'json' => [
                'action' => 'set-auth', 'kind' => 'bearer', 'host' => 'https://not a host/', 'secret' => 'x',
            ]])
        );

        $this->assertEquals(422, $response->getStatusCode());
        $error = json_decode((string) $response->getBody(), true)['errors'][0];
        $this->assertSame('refused', $error['code']);
        $this->assertStringContainsString('hostname', $error['detail']);
    }

    #[Test]
    public function starting_with_nothing_selected_is_a_validation_error(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/extension-manager/update', ['authenticatedAs' => 1, 'json' => ['packages' => []]])
        );

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertSame('validation_error', json_decode((string) $response->getBody(), true)['errors'][0]['code']);
    }

    #[Test]
    public function a_refusal_carries_its_own_type_and_status(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/extension-manager/rollback', ['authenticatedAs' => 1])
        );

        $this->assertEquals(404, $response->getStatusCode());
        $error = json_decode((string) $response->getBody(), true)['errors'][0];
        $this->assertSame('nothing_to_roll_back', $error['code']);
        $this->assertNotEmpty($error['detail']);
    }
}
