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
use Flarum\Ai\Models\ModelCatalog;
use Flarum\Ai\Models\ModelHealth;
use Flarum\Ai\Models\TemperatureSupport;
use Flarum\Frontend\Document;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * What the admin page needs beyond the settings: which connections have a
 * key, the providers it can set up, what config.php fixes (shown, not
 * editable), the features extensions have registered, the models in use that
 * are deprecated or gone, and the rules its forms check before saving.
 *
 * @internal
 */
class AdminPayload
{
    public function __construct(
        protected AdminConnections $connections,
        protected ConnectionKeys $keys,
        protected AiConfig $config,
        protected FeatureRegistry $features,
        protected ModelHealth $health,
        protected TemperatureSupport $temperatures,
        protected ModelCatalog $catalog
    ) {
    }

    public function __invoke(Document $document, Request $request): void
    {
        $document->payload['flarum-ai'] = [
            'connectionsWithKeys' => $this->keys->names(),
            'drivers' => Drivers::admin(),
            'keyOptional' => Drivers::keyOptional($this->config->bedrockServerCredentials()),
            'urlRequired' => Drivers::URL_REQUIRED,
            'regionRequired' => Drivers::REGION_REQUIRED,
            // Whether Bedrock can be offered, or the page says what to install.
            'awsSdk' => Drivers::awsSdkInstalled(),
            'lockedConnections' => (object) $this->connections->forAdmin($this->config->lockedConnections()),
            'lockedDefault' => $this->config->lockedDefaultConnection(),
            'features' => array_values(array_map(fn (Feature $feature) => [
                'key' => $feature->key,
                'label' => $feature->label,
                'description' => $feature->description,
                'extension' => $feature->extension,
                'permission' => $feature->permission(),
            ], $this->features->all())),
            // On every admin page, so the dashboard can warn too.
            'modelIssues' => $this->health->forAdmin(),
            // Models a feature's temperature is left out for, as they refuse one.
            'temperatureIgnored' => $this->temperatures->all(),
            // The model each connection uses when a feature names none.
            'defaultModels' => array_filter(array_map(
                fn (string $name) => $this->catalog->defaultModel($name),
                array_combine($names = array_keys($this->config->connections()), $names)
            )),
            'rules' => [
                'namePattern' => AdminConnections::NAME_PATTERN,
                'modelMaxLength' => AdminConnections::MODEL_MAX_LENGTH,
                'regionPattern' => AdminConnections::REGION_PATTERN,
                'guardrailPattern' => AdminConnections::GUARDRAIL_PATTERN,
                'guardrailVersionPattern' => AdminConnections::GUARDRAIL_VERSION_PATTERN,
            ],
        ];
    }
}
