<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Exception;
use Flarum\Api\Resource\ForumResource;
use Flarum\Api\Schema\Boolean;
use Flarum\Extend;
use Flarum\Extension\ExtensionManager;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class ConditionalTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** @test */
    public function conditional_works_if_condition_is_primitive_true()
    {
        $this->extend(
            (new Extend\Conditional())
                ->when(true, fn () => [
                    (new Extend\ApiResource(ForumResource::class))
                        ->fields(fn () => [
                            Boolean::make('customConditionalAttribute')
                                ->get(fn () => true)
                        ])
                ])
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customConditionalAttribute', $payload['data']['attributes']);
    }

    /** @test */
    public function conditional_does_not_work_if_condition_is_primitive_false()
    {
        $this->extend(
            (new Extend\Conditional())
                ->when(false, fn () => [
                    (new Extend\ApiResource(ForumResource::class))
                        ->fields(fn () => [
                            Boolean::make('customConditionalAttribute')
                                ->get(fn () => true)
                        ])
                ])
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('customConditionalAttribute', $payload['data']['attributes']);
    }

    /** @test */
    public function conditional_works_if_condition_is_callable_true()
    {
        $this->extend(
            (new Extend\Conditional())
                ->when(fn () => true, fn () => [
                    (new Extend\ApiResource(ForumResource::class))
                        ->fields(fn () => [
                            Boolean::make('customConditionalAttribute')
                                ->get(fn () => true)
                        ])
                ])
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customConditionalAttribute', $payload['data']['attributes']);
    }

    /** @test */
    public function conditional_does_not_work_if_condition_is_callable_false()
    {
        $this->extend(
            (new Extend\Conditional())
                ->when(fn () => false, fn () => [
                    (new Extend\ApiResource(ForumResource::class))
                        ->fields(fn () => [
                            Boolean::make('customConditionalAttribute')
                                ->get(fn () => true)
                        ])
                ])
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('customConditionalAttribute', $payload['data']['attributes']);
    }

    /** @test */
    public function conditional_injects_dependencies_to_condition_callable()
    {
        $this->expectNotToPerformAssertions();

        $this->extend(
            (new Extend\Conditional())
                ->when(function (?ExtensionManager $extensions) {
                    if (! $extensions) {
                        throw new Exception('ExtensionManager not injected');
                    }
                }, fn () => [
                    (new Extend\ApiResource(ForumResource::class))
                        ->fields(fn () => [
                            Boolean::make('customConditionalAttribute')
                                ->get(fn () => true)
                        ])
                ])
        );

        $this->app();
    }

    /** @test */
    public function conditional_disabled_extension_not_enabled_applies_invokable_class()
    {
        $this->extend(
            (new Extend\Conditional())
                ->whenExtensionDisabled('flarum-dummy-extension', TestExtender::class)
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customConditionalAttribute', $payload['data']['attributes']);
    }

    /** @test */
    public function conditional_disabled_extension_enabled_does_not_apply_invokable_class()
    {
        $this->extension('flarum-tags');

        $this->extend(
            (new Extend\Conditional())
                ->whenExtensionDisabled('flarum-tags', TestExtender::class)
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('customConditionalAttribute', $payload['data']['attributes']);
    }

    /** @test */
    public function conditional_enabled_extension_disabled_does_not_apply_invokable_class()
    {
        $this->extend(
            (new Extend\Conditional())
                ->whenExtensionEnabled('flarum-dummy-extension', TestExtender::class)
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('customConditionalAttribute', $payload['data']['attributes']);
    }

    /** @test */
    public function conditional_enabled_extension_enabled_applies_invokable_class()
    {
        $this->extension('flarum-tags');
        $this->extend(
            (new Extend\Conditional())
                ->whenExtensionEnabled('flarum-tags', TestExtender::class)
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customConditionalAttribute', $payload['data']['attributes']);
    }

    /** @test */
    public function conditional_does_not_instantiate_extender_if_condition_is_false_using_callable()
    {
        $this->extend(
            (new Extend\Conditional())
                ->when(false, TestExtender::class)
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('customConditionalAttribute', $payload['data']['attributes']);
    }

    /** @test */
    public function conditional_does_instantiate_extender_if_condition_is_true_using_callable()
    {
        $this->extend(
            (new Extend\Conditional())
                ->when(true, TestExtender::class)
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customConditionalAttribute', $payload['data']['attributes']);
    }

    /** @test */
    public function conditional_does_not_instantiate_extender_if_condition_is_false_using_callback()
    {
        $this->extend(
            (new Extend\Conditional())
                ->when(false, fn () => [
                    (new Extend\ApiResource(ForumResource::class))
                        ->fields(fn () => [
                            Boolean::make('customConditionalAttribute')
                                ->get(fn () => true)
                        ])
                ])
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('customConditionalAttribute', $payload['data']['attributes']);
    }

    /** @test */
    public function conditional_does_instantiate_extender_if_condition_is_true_using_callback()
    {
        $this->extend(
            (new Extend\Conditional())
                ->when(true, fn () => [
                    (new Extend\ApiResource(ForumResource::class))
                        ->fields(fn () => [
                            Boolean::make('customConditionalAttribute')
                                ->get(fn () => true)
                        ])
                ])
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customConditionalAttribute', $payload['data']['attributes']);
    }

    /** @test */
    public function conditional_does_not_work_if_extension_is_disabled()
    {
        $this->extend(
            (new Extend\Conditional())
                ->whenExtensionEnabled('dummy-extension-id', TestExtender::class)
        );

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('customConditionalAttribute', $payload['data']['attributes']);
    }
}

class TestExtender
{
    public function __invoke(): array
    {
        return [
            (new Extend\ApiResource(ForumResource::class))
                ->fields(fn () => [
                    Boolean::make('customConditionalAttribute')
                        ->get(fn () => true)
                ])
        ];
    }
}
