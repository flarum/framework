<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Frontend;

use Flarum\Frontend\Document;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class DocumentTest extends TestCase
{
    #[Test]
    #[DataProvider('pageParamUrls')]
    public function set_page_param_builds_the_right_url(string $url, ?int $page, string $expected)
    {
        $this->assertSame($expected, Document::setPageParam($url, $page));
    }

    public static function pageParamUrls(): array
    {
        return [
            'existing query keeps port and user info' => [
                'http://user:pass@localhost:8080/d/1-example?sort=newest&page=2#post',
                3,
                'http://user:pass@localhost:8080/d/1-example?sort=newest&page=3#post',
            ],
            'page 1 drops the question mark when no query is left' => [
                'http://localhost:8080/d/1-example?page=2',
                1,
                'http://localhost:8080/d/1-example',
            ],
            'page 1 keeps the other params' => [
                'http://localhost:8080/d/1-example?sort=newest&page=2',
                1,
                'http://localhost:8080/d/1-example?sort=newest',
            ],
            'page 1 with a query keeps the fragment' => [
                'http://localhost:8080/?sort=top&page=2#discussions',
                1,
                'http://localhost:8080/?sort=top#discussions',
            ],
            'no query keeps the port' => [
                'http://localhost:8080/d/1-example',
                2,
                'http://localhost:8080/d/1-example?page=2',
            ],
            'no query puts the page before the fragment' => [
                'http://localhost:8080#discussions',
                3,
                'http://localhost:8080?page=3#discussions',
            ],
            'no query and page 1 leaves the url alone' => [
                'http://localhost:8080/#discussions',
                1,
                'http://localhost:8080/#discussions',
            ],
        ];
    }
}
