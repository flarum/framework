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
 * Id 0 is a valid lookup that finds nothing. Endpoint::handle() used to check
 * the id for truthiness, and "0" is falsy, so the lookup was skipped and the
 * endpoint got a null model: a 500 on every database instead of a 404.
 */
class ModelIdZeroTest extends TestCase
{
    public static function paths(): array
    {
        $cases = [];

        foreach (['/api/users/0', '/api/discussions/0', '/api/posts/0', '/api/groups/0', '/api/notifications/0'] as $path) {
            $cases[$path] = [$path];
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('paths')]
    public function id_zero_is_not_found(string $path)
    {
        $response = $this->send(
            $this->request('GET', $path, ['authenticatedAs' => 1])
        );

        $this->assertEquals(404, $response->getStatusCode(), (string) $response->getBody());
    }
}
