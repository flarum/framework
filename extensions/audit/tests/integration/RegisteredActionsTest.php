<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Flarum\Audit\AuditLogger;
use PHPUnit\Framework\Attributes\Test;

/**
 * Guards that action strings are registered in AuditLogger::$registeredActions when the app
 * boots, so they surface in the admin settings and the search autocomplete.
 *
 * This is distinct from the per-action logging tests: an action can be logged correctly while
 * never being registered for display (the regression that hid user.password_change_requested
 * and the other integration actions from the UI).
 */
class RegisteredActionsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-audit');
    }

    private function registeredActions(): array
    {
        // Boot the application so every extender's extend() runs and registrations are flushed.
        $this->app();

        $all = [];
        foreach (AuditLogger::$registeredActions as $actions) {
            $all = array_merge($all, $actions);
        }

        return array_unique($all);
    }

    #[Test]
    public function core_user_actions_are_registered()
    {
        $registered = $this->registeredActions();

        // The action that regressed, plus a representative sample of the core user set.
        $expected = [
            'user.password_change_requested',
            'user.password_changed',
            'user.logged_in',
            'user.email_changed',
            'user.groups_changed',
        ];

        foreach ($expected as $action) {
            $this->assertContains($action, $registered, "Expected core user action '$action' to be registered");
        }
    }

    #[Test]
    public function core_actions_are_registered()
    {
        $registered = $this->registeredActions();

        // Actions owned by the audit extension itself (its own inline listeners). Actions
        // contributed by other extensions are each extension's responsibility to register
        // and to test — audit does not assert their presence here.
        $expected = [
            'audit_log_cleared',
            'cache_cleared',
            'setting_changed',
            'settings_reset',
            'permission_changed',
            'discussion.created',
            'post.created',
            'group.created',
            'group.renamed',
            'group.deleted',
            'developer_token_created',
        ];

        foreach ($expected as $action) {
            $this->assertContains($action, $registered, "Expected action '$action' to be registered");
        }
    }

    #[Test]
    public function actions_are_grouped_under_core()
    {
        $this->app();

        $this->assertArrayHasKey('core', AuditLogger::$registeredActions);
        $this->assertContains('user.password_change_requested', AuditLogger::$registeredActions['core']);
    }
}
