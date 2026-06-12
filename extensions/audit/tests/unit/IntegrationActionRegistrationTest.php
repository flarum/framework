<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\unit;

use Flarum\Audit\Extend\Audit;
use Flarum\Audit\Integration\CoreSettingIntegration;
use Flarum\Audit\Integration\CoreUserIntegration;
use Flarum\Audit\Integration\FoFUsernameRequestIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Guards the registration of action strings declared by integration classes.
 *
 * Integrations are wired into the audit extender via `->using(new SomeIntegration())`, which
 * attaches their event listeners. Their action vocabulary lives in a public static `$actions`
 * array on the integration. The extender must harvest those actions so they appear in the
 * admin settings and the search autocomplete.
 *
 * This previously regressed: the integrations logged fine, but their actions were never
 * registered, so e.g. `user.password_change_requested` was invisible in the UI. These tests
 * lock the behaviour in.
 */
class IntegrationActionRegistrationTest extends TestCase
{
    /**
     * Read the protected `$actions` the extender accumulated.
     */
    private function harvested(Audit $extender): array
    {
        $property = new ReflectionProperty(Audit::class, 'actions');
        $property->setAccessible(true);

        return $property->getValue($extender);
    }

    #[Test]
    #[DataProvider('integrations')]
    public function using_an_integration_registers_its_declared_actions(string $integrationClass)
    {
        $declared = $integrationClass::$actions;

        $this->assertNotEmpty($declared, "$integrationClass should declare at least one action");

        $extender = (new Audit())->using(new $integrationClass());

        $harvested = $this->harvested($extender);

        foreach ($declared as $action) {
            $this->assertContains($action, $harvested, "$integrationClass action '$action' should be registered by using()");
        }
    }

    #[Test]
    public function user_password_change_requested_is_registered()
    {
        // The specific action whose registration regressed.
        $extender = (new Audit())->using(new CoreUserIntegration());

        $this->assertContains('user.password_change_requested', $this->harvested($extender));
    }

    #[Test]
    public function register_and_listen_also_contribute_actions()
    {
        $extender = (new Audit())
            ->register('manual_action')
            ->listen('SomeEvent', 'listened_action', function () {
                return [];
            });

        $harvested = $this->harvested($extender);

        $this->assertContains('manual_action', $harvested);
        $this->assertContains('listened_action', $harvested);
    }

    public static function integrations(): array
    {
        // First-party extension integrations (flags, nicknames, tags) now live in their own
        // extensions and are covered by those extensions' AuditTest suites. Only the core and
        // bundled-thirdparty integrations that still ship with flarum/audit are exercised here.
        return [
            'core user' => [CoreUserIntegration::class],
            'core setting' => [CoreSettingIntegration::class],
            'fof username request' => [FoFUsernameRequestIntegration::class],
        ];
    }
}
