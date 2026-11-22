<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * An id that is not a whole number, or one beyond the key column's range,
 * cannot be a model's integer key. Passed to the database, MySQL reads "1abc"
 * as 1 and returns that record, while PostgreSQL rejects the query.
 */
class NonIntegerIdTest extends TestCase
{
    public static function paths(): array
    {
        $cases = [];

        foreach (['/api/users', '/api/discussions', '/api/posts', '/api/groups', '/api/notifications'] as $collection) {
            foreach (['1abc', '1.5', '1e0', '2147483648', '99999999999999999999'] as $id) {
                $cases["$collection/$id"] = ["$collection/$id"];
            }
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('paths')]
    public function id_that_cannot_be_a_key_is_not_found(string $path)
    {
        $response = $this->send(
            $this->request('GET', $path, ['authenticatedAs' => 1])
        );

        $this->assertEquals(404, $response->getStatusCode(), (string) $response->getBody());
    }

    public static function slugPaths(): array
    {
        return [
            'discussion page' => ['/d/2147483648', []],
            'discussion page with title' => ['/d/2147483648-title', []],
            'profile with the id driver' => ['/u/2147483648', ['slug_driver_'.User::class => 'id']],
            'profile with the id and name driver' => ['/u/2147483648-name', ['slug_driver_'.User::class => 'id_with_display_name']],
        ];
    }

    #[Test]
    #[DataProvider('slugPaths')]
    public function slug_with_an_id_beyond_the_integer_range_is_not_found(string $path, array $settings)
    {
        foreach ($settings as $key => $value) {
            $this->setting($key, $value);
        }

        $response = $this->send(
            $this->request('GET', $path, ['authenticatedAs' => 1])
        );

        $this->assertEquals(404, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function zero_padded_id_still_finds_the_record()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/01', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }
}
