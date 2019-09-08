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

use Flarum\Install\Installation;
use Flarum\Tests\integration\TestCase;

class InstallationTest extends TestCase
{
    private $installation;

    public function setUp()
    {
        $this->installation = new Installation('', '', '', '');
    }

    /**
     * @dataProvider cliBaseUrlProvider
     * @param $uri
     * @param $expected
     */
    public function test_normalise_base_url_via_cli($uri, $expected)
    {
        $this->assertEquals($expected, $this->installation->normaliseBaseUrl($uri));
    }

    public function cliBaseUrlProvider()
    {
        return [
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

    /**
     * @dataProvider webBaseUrlProvider
     * @param $uri
     * @param $expected
     */
    public function test_normalise_base_url_via_web($uri, $expected)
    {
        $request = $this->request('get', $uri);
        $this->assertEquals($expected, $this->installation->normaliseBaseUrl($request->getUri()));
    }

    public function webBaseUrlProvider()
    {
        return [
            ['http://flarum.org',                    'http://flarum.org'],
            ['http://flarum.org/',                   'http://flarum.org'],
            ['https://flarum.org',                   'https://flarum.org'],
            ['http://flarum.org/index.php',          'http://flarum.org'],
            ['http://flarum.org/index.php/',         'http://flarum.org'],
            ['http://flarum.org/flarum',             'http://flarum.org/flarum'],
            ['http://flarum.org/flarum/',            'http://flarum.org/flarum'],
            ['http://flarum.org/flarum/index.php',   'http://flarum.org/flarum'],
            ['http://flarum.org/flarum/index.php/',  'http://flarum.org/flarum'],
            ['http://sub.flarum.org',                'http://sub.flarum.org'],
            ['http://sub.flarum.org/',               'http://sub.flarum.org'],
        ];
    }
}
