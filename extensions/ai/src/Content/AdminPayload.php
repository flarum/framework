<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Content;

use Carbon\Carbon;
use Flarum\Ai\AdminConnections;
use Flarum\Ai\AiConfig;
use Flarum\Ai\ConnectionKeys;
use Flarum\Ai\Drivers;
use Flarum\Ai\Features\Feature;
use Flarum\Ai\Features\FeatureRegistry;
use Flarum\Ai\Models\ModelCatalog;
use Flarum\Ai\Models\ModelHealth;
use Flarum\Ai\Models\ModelLister;
use Flarum\Ai\Models\TemperatureSupport;
use Flarum\Ai\Usage\Ledger;
use Flarum\Frontend\Document;
use Illuminate\Contracts\Cache\Repository as Cache;
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
        protected ModelCatalog $catalog,
        protected Ledger $ledger,
        protected Cache $cache
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
            // The address each provider is reached at when a connection gives none.
            'defaultUrls' => ModelLister::DEFAULT_URLS,
            // Tokens each connection has used this month, against its monthly limit.
            'monthlyUsage' => (object) $this->ledger->thisMonthByConnection(),
            // When the scheduler last ran the daily model check and usage clean-up.
            'schedulerLastRun' => $this->schedulerLastRun(),
            'rules' => [
                'namePattern' => AdminConnections::NAME_PATTERN,
                'modelMaxLength' => AdminConnections::MODEL_MAX_LENGTH,
                'regionPattern' => AdminConnections::REGION_PATTERN,
                'guardrailPattern' => AdminConnections::GUARDRAIL_PATTERN,
                'guardrailVersionPattern' => AdminConnections::GUARDRAIL_VERSION_PATTERN,
            ],
        ];
    }

    /**
     * Core notes each run of `php flarum schedule:run`.
     */
    protected function schedulerLastRun(): ?string
    {
        $lastRun = $this->cache->get('flarum:schedule:last_run');

        return $lastRun ? Carbon::parse($lastRun)->toIso8601String() : null;
    }
}
