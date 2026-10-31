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
use Flarum\Ai\ConnectionKeys;
use Flarum\Ai\Drivers;
use Flarum\Ai\Features\Feature;
use Flarum\Ai\Features\FeatureRegistry;
use Flarum\Frontend\Document;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * What the admin page needs beyond the settings: which connections have a
 * key, the providers it can set up, what config.php fixes (shown, not
 * editable), the features extensions have registered, and the rules its
 * forms check before saving.
 *
 * @internal
 */
class AdminPayload
{
    public function __construct(
        protected AdminConnections $connections,
        protected ConnectionKeys $keys,
        protected AiConfig $config,
        protected FeatureRegistry $features
    ) {
    }

    public function __invoke(Document $document, Request $request): void
    {
        $document->payload['flarum-ai'] = [
            'connectionsWithKeys' => $this->keys->names(),
            'drivers' => Drivers::ADMIN,
            'keyOptional' => Drivers::KEY_OPTIONAL,
            'urlRequired' => Drivers::URL_REQUIRED,
            'lockedConnections' => (object) $this->connections->forAdmin($this->config->lockedConnections()),
            'lockedDefault' => $this->config->lockedDefaultConnection(),
            'features' => array_values(array_map(fn (Feature $feature) => [
                'key' => $feature->key,
                'label' => $feature->label,
                'description' => $feature->description,
                'extension' => $feature->extension,
                'permission' => $feature->permission(),
            ], $this->features->all())),
            'rules' => [
                'namePattern' => AdminConnections::NAME_PATTERN,
                'modelMaxLength' => AdminConnections::MODEL_MAX_LENGTH,
            ],
        ];
    }
}
