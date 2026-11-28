<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Audit;

use Flarum\Audit\AuditLogger;
use Flarum\Mcp\Event\Authenticated;

class RecordMcpActor
{
    public function handle(Authenticated $event): void
    {
        AuditLogger::$actor = $event->authentication->actor;
        AuditLogger::$client = 'mcp';
    }
}
