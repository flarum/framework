<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Testing\integration\TestCase;

class ThemeTest extends TestCase
{
    /**
     * @test
     */
    public function theme_extender_override_import_works()
    {
        $this->extend(
            (new Extend\Theme)
                ->overrideLessImport('forum/Hero.less', __DIR__.'/../../fixtures/less/dummy.less')
        );

        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function theme_extender_override_import_works_by_failing_when_necessary()
    {
        $this->extend(
            (new Extend\Frontend('forum'))
                ->css(__DIR__.'/../../fixtures/less/forum.less'),
            (new Extend\Theme)
                ->overrideLessImport('Imported.less', __DIR__.'/../../fixtures/less/dummy.less', 'site-custom')
        );

        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(500, $response->getStatusCode());
    }
}
