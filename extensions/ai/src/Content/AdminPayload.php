<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Content;

use Flarum\Ai\AdminConnections;
use Flarum\Ai\AiConfig;
use Flarum\Ai\Drivers;
use Flarum\Frontend\Document;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * What the admin page needs beyond the settings: the providers it can set up,
 * and what config.php fixes, which the page shows but can't change.
 */
class AdminPayload
{
    public function __construct(
        protected AdminConnections $connections,
        protected AiConfig $config
    ) {
    }

    public function __invoke(Document $document, Request $request): void
    {
        $document->payload['flarum-ai'] = [
            'drivers' => Drivers::ADMIN,
            'keyOptional' => Drivers::KEY_OPTIONAL,
            'urlRequired' => Drivers::URL_REQUIRED,
            'lockedConnections' => (object) $this->connections->withoutKeys($this->config->lockedConnections()),
            'lockedDefault' => $this->config->lockedDefaultConnection(),
        ];
    }
}
