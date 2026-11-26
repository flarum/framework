<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\forum;

use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The search UI only sends a query once it reaches the minimum length, and
 * may adapt to CJK mode, so the forum frontend must be able to read both:
 * they are exposed on the forum resource.
 */
class SearchSettingsTest extends TestCase
{
    #[Test]
    public function guests_see_the_default_minimum()
    {
        $response = $this->send(
            $this->request('GET', '/api')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertSame(3, Arr::get($json, 'data.attributes.searchMinLength'));
    }

    #[Test]
    public function guests_see_the_configured_minimum()
    {
        $this->setting('search_min_length', '2');

        $response = $this->send(
            $this->request('GET', '/api')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertSame(2, Arr::get($json, 'data.attributes.searchMinLength'));
    }

    #[Test]
    #[DataProvider('unusableStoredMinimums')]
    public function an_unusable_stored_value_falls_back_to_the_default(string $value)
    {
        $this->setting('search_min_length', $value);

        $response = $this->send(
            $this->request('GET', '/api')
        );

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertSame(3, Arr::get($json, 'data.attributes.searchMinLength'));
    }

    public static function unusableStoredMinimums(): array
    {
        return [
            'zero' => ['0'],
            'negative' => ['-2'],
            'not a number' => ['abc'],
            'empty' => [''],
        ];
    }

    #[Test]
    public function cjk_mode_does_not_change_the_configured_minimum()
    {
        $this->setting('search_cjk_mode', '1');

        $response = $this->send(
            $this->request('GET', '/api')
        );

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertSame(3, Arr::get($json, 'data.attributes.searchMinLength'));
    }

    #[Test]
    public function guests_see_that_cjk_mode_is_off_by_default()
    {
        $response = $this->send($this->request('GET', '/api'));
        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertFalse(Arr::get($json, 'data.attributes.searchCjkMode'));
    }

    #[Test]
    public function guests_see_that_cjk_mode_is_on()
    {
        $this->setting('search_cjk_mode', '1');

        $response = $this->send($this->request('GET', '/api'));
        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertTrue(Arr::get($json, 'data.attributes.searchCjkMode'));
    }

    #[Test]
    public function admin_can_save_a_valid_minimum()
    {
        $response = $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => ['search_min_length' => '1'],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals('1', $this->app->getContainer()->make('flarum.settings')->get('search_min_length'));
    }

    #[Test]
    #[DataProvider('invalidMinimums')]
    public function admin_cannot_save_an_invalid_minimum(mixed $value)
    {
        $response = $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => ['search_min_length' => $value],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(3, $this->app->getContainer()->make('flarum.settings')->get('search_min_length'));
    }

    public static function invalidMinimums(): array
    {
        return [
            'zero' => ['0'],
            'negative' => ['-1'],
            'not a number' => ['abc'],
            'decimal' => ['1.5'],
            'empty' => [''],
        ];
    }
}
