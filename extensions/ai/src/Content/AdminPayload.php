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
use Flarum\Ai\Features\Feature;
use Flarum\Ai\Features\FeatureRegistry;
use Flarum\Frontend\Document;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * What the admin page needs beyond the settings: the providers it can set up,
 * what config.php fixes, which the page shows but can't change, and the
 * features extensions have registered.
 */
class AdminPayload
{
    public function __construct(
        protected AdminConnections $connections,
        protected AiConfig $config,
        protected FeatureRegistry $features
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
            'features' => array_values(array_map(fn (Feature $feature) => [
                'key' => $feature->key,
                'label' => $feature->label,
                'description' => $feature->description,
                'extension' => $feature->extension,
                'permission' => $feature->permission(),
            ], $this->features->all())),
        ];
    }
}
