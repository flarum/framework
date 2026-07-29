<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Flarum\Audit\AuditLog;
use PHPUnit\Framework\Attributes\Test;

class CoreExtensionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // An extension we will disable from the tests
        // It could be any extension, we're just using extensions we already depend on
        $this->extension('flarum-lock');
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } catch (\PDOException $exception) {
            // Getting "PDOException: There is no active transaction"
            // Probably for the same reason as truncate code below
        }

        // It seems like these tests escape the transaction that usually wraps tests
        // Probably because of the database migrator running during extension enable/disable
        // So we'll clean up the log table for the next test
        AuditLog::query()->truncate();
    }

    #[Test]
    public function disabled()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/extensions/flarum-lock', [
            'json' => [
                'enabled' => false,
            ],
        ], 204);

        $this->assertLogExists('extension.disabled', [
            'package' => 'flarum/lock',
        ]);
    }

    #[Test]
    public function enabled()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/extensions/flarum-sticky', [
            'json' => [
                'enabled' => true,
            ],
        ], 204);

        $this->assertLogExists('extension.enabled', [
            'package' => 'flarum/sticky',
        ]);
    }

    #[Test]
    public function uninstalled()
    {
        $this->sendSuccessfulRequest('DELETE', '/api/extensions/flarum-sticky', [], 204);

        $this->assertLogExists('extension.uninstalled', [
            'package' => 'flarum/sticky',
        ]);
    }
}
