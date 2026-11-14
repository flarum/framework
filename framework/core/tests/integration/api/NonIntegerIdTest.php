<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * An id that is not a whole number cannot be a model's integer key. Passed to
 * the database, MySQL read "1abc" as 1 and returned that record, while
 * PostgreSQL rejected the query with a 500. It is now not found everywhere.
 */
class NonIntegerIdTest extends TestCase
{
    public static function paths(): array
    {
        $cases = [];

        foreach (['/api/users', '/api/discussions', '/api/posts', '/api/groups', '/api/notifications'] as $collection) {
            foreach (['1abc', '1.5', '1e0'] as $id) {
                $cases["$collection/$id"] = ["$collection/$id"];
            }
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('paths')]
    public function non_integer_id_is_not_found(string $path)
    {
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
