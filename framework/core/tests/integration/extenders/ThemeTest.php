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
use PHPUnit\Framework\Attributes\Test;

class ThemeTest extends TestCase
{
    #[Test]
    public function theme_extender_override_import_doesnt_work_by_default()
    {
        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode());

        $cssFilePath = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets')->path('forum.css');
        $this->assertStringNotContainsString('.dummy_test_case{color:red}', file_get_contents($cssFilePath));
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function theme_extender_override_file_source_works_by_failing_when_necessary()
    {
        $this->extend(
            (new Extend\Theme)
                ->overrideFileSource('mixins.less', __DIR__.'/../../fixtures/less/dummy.less')
        );

        $response = $this->send($this->request('GET', '/'));

        $this->assertStringContainsString('Less_Exception_Compiler', $response->getBody()->getContents());
        $this->assertEquals(500, $response->getStatusCode());
    }

    #[Test]
    public function theme_extender_can_add_custom_function()
    {
        $this->extend(
            (new Extend\Frontend('forum'))
                ->css(__DIR__.'/../../fixtures/less/custom_function.less'),
            (new Extend\Theme)
                ->addCustomLessFunction('is-flarum', function ($text) {
                    return strtolower($text) === 'flarum' ? 'true' : 100;
                })
                ->addCustomLessFunction('is-gt', function ($a, $b) {
                    return $a > $b;
                })
        );

        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode());

        $cssFilePath = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets')->path('forum.css');
        $contents = file_get_contents($cssFilePath);

        $this->assertStringContainsString('.dummy_func_test{color:green}', $contents);
        $this->assertStringContainsString('.dummy_func_test2{--x:1000;--y:false}', $contents);
    }

    #[Test]
    public function theme_extender_can_add_custom_variable()
    {
        $this->extend(
            (new Extend\Frontend('forum'))
                ->css(__DIR__.'/../../fixtures/less/custom_variable.less'),
            (new Extend\Theme)
                ->addCustomLessVariable('doesnt-exist', function () {
                    return 'it does';
                })
        );

        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());

        $cssFilePath = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets')->path('forum.css');
        $contents = file_get_contents($cssFilePath);

        $this->assertStringContainsString('.dummy_var_test{--x:it does}', $contents);
    }
}
