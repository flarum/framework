<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Testing;

class TestCase extends \Flarum\Testing\integration\TestCase
{
    use InteractsWithAuditLog;
    use BuildsAuditRequests;

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            $this->tearDownAuditLog();
        }
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpAuditLog();
        $this->exemptAuditAuthRoutesFromCsrf();

        $this->extension('flarum-audit');
    }
}
