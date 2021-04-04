<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\TestCase;

class DefaultRouteTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => 'foo bar', 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>']
            ]
        ]);
    }

    /**
     * This is necessary as we need to add the setting to the DB before the app boots.
     */
    protected function setDefaultRoute($defaultRoute)
    {
        OverrideDefaultRouteServiceProvider::$defaultRoute = $defaultRoute;
        $this->extend(
            (new Extend\ServiceProvider())->register(OverrideDefaultRouteServiceProvider::class)
        );
    }

    /**
     * @test
     */
    public function default_route_payload_includes_discussions()
    {
        $response = $this->send(
            $this->request('GET', '/')
        );

        $this->assertStringContainsString('apiDocument', $response->getBody());
    }

    /**
     * @test
     */
    public function nonexistent_custom_homepage_uses_default_payload()
    {
        $this->setDefaultRoute('/nonexistent');

        $response = $this->send(
            $this->request('GET', '/')
        );

        $this->assertStringContainsString('apiDocument', $response->getBody());
    }

    /**
     * @test
     */
    public function existent_custom_homepage_doesnt_use_default_payload()
    {
        $this->setDefaultRoute('/settings');

        $response = $this->send(
            $this->request('GET', '/')
        );

        $this->assertStringNotContainsString('apiDocument', $response->getBody());
    }
}

class OverrideDefaultRouteServiceProvider extends AbstractServiceProvider
{
    public static $defaultRoute;

    public function register()
    {
        $settings = $this->container->make(SettingsRepositoryInterface::class);

        $settings->set('default_route', static::$defaultRoute);
    }
}
