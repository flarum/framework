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
    public function theme_extender_override_import_doesnt_work_by_default()
    {
        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode());

        $cssFilePath = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets')->path('forum.css');
        $this->assertStringNotContainsString('.dummy_test_case{color:red}', file_get_contents($cssFilePath));
    }

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

        $cssFilePath = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets')->path('forum.css');

        $this->assertStringContainsString('.dummy_test_case{color:red}', file_get_contents($cssFilePath));
    }

    /**
     * @test
     */
    public function theme_extender_override_import_works_with_external_sources()
    {
        $this->extend(
            (new Extend\Frontend('forum'))
                ->css(__DIR__.'/../../fixtures/less/forum.less'),
            (new Extend\Theme)
                ->overrideLessImport('Imported.less', __DIR__.'/../../fixtures/less/dummy.less', 'site-custom')
        );

        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode());

        $cssFilePath = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets')->path('forum.css');
        $contents = file_get_contents($cssFilePath);

        $this->assertStringNotContainsString('.Imported', $contents);
        $this->assertStringContainsString('.dummy_test_case{color:red}', $contents);
        $this->assertStringContainsString('.dummy{color:yellow}', $contents);
    }

    /**
     * @test
     */
    public function theme_extender_override_file_source_works()
    {
        $this->extend(
            (new Extend\Theme)
                ->overrideFileSource('forum.less', __DIR__.'/../../fixtures/less/override_filesource.less')
        );

        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode());

        $cssFilePath = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets')->path('forum.css');

        $this->assertEquals('body{color:orange}', file_get_contents($cssFilePath));
    }

    /**
     * @test
     */
    public function theme_extender_override_file_source_works_by_failing_when_necessary()
    {
        $this->extend(
            (new Extend\Theme)
                ->overrideFileSource('mixins.less', __DIR__.'/../../fixtures/less/dummy.less')
        );

        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(500, $response->getStatusCode());
    }
}
