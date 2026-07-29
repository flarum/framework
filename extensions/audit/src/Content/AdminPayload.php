<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Content;

use Flarum\Audit\AuditLogger;
use Flarum\Frontend\Document;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminPayload
{
    public function __invoke(Document $document, Request $request): void
    {
        $document->payload['auditLogActions'] = AuditLogger::$registeredActions;
    }
}
