<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\unit;

use Flarum\Install\BaseUrl;
use Flarum\Tests\integration\TestCase;

class BaseUrlTest extends TestCase
{
    /**
     * @dataProvider urlProvider
     * @param $uri
     * @param $expected
     */
    public function test_base_url_simulating_cli_installer($uri, $expected)
    {
        $this->assertEquals($expected, BaseUrl::fromString($uri));
    }

    /**
     * @dataProvider urlProvider
     * @param $uri
     * @param $expected
     */
    public function test_base_url_simulating_web_installer($uri, $expected)
    {
        $request = $this->request('get', $uri);

        $this->assertEquals($expected, BaseUrl::fromUri($request->getUri()));
    }

    public function urlProvider()
    {
        return [
            ['',                                    ''],
            ['flarum.org',                          'http://flarum.org'],
            ['flarum.org/',                         'http://flarum.org'],
            ['http://flarum.org',                   'http://flarum.org'],
            ['http://flarum.org/',                  'http://flarum.org'],
            ['https://flarum.org',                  'https://flarum.org'],
            ['http://flarum.org/index.php',         'http://flarum.org'],
            ['http://flarum.org/index.php/',        'http://flarum.org'],
            ['http://flarum.org/flarum',            'http://flarum.org/flarum'],
            ['http://flarum.org/flarum/index.php',  'http://flarum.org/flarum'],
            ['http://flarum.org/flarum/index.php/', 'http://flarum.org/flarum'],
            ['sub.flarum.org',                      'http://sub.flarum.org'],
            ['http://sub.flarum.org',               'http://sub.flarum.org'],
        ];
    }
}
