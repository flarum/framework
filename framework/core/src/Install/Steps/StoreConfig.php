<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Steps;

use Flarum\Install\BaseUrl;
use Flarum\Install\DatabaseConfig;
use Flarum\Install\ReversibleStep;

readonly class StoreConfig implements ReversibleStep
{
    public function __construct(
        private bool $debugMode,
        private DatabaseConfig $dbConfig,
        private BaseUrl $baseUrl,
        private string $configFile
    ) {
    }

    public function getMessage(): string
    {
        return 'Writing config file';
    }

    public function run(): void
    {
        file_put_contents(
            $this->configFile,
            '<?php return '.var_export($this->buildConfig(), true).';'
        );
    }

    public function revert(): void
    {
        @unlink($this->configFile);
    }

    private function buildConfig(): array
    {
        return [
            'debug' => $this->debugMode,
            'database' => $this->dbConfig->toArray(),
            'url' => (string) $this->baseUrl,
            'paths' => $this->getPathsConfig(),
            'headers' => [
                'poweredByHeader' => true,
                'referrerPolicy' => 'same-origin',
            ],
            'queue' => [
                'driver' => 'sync'
            ]
        ];
    }

    private function getPathsConfig(): array
    {
        return [
            'api' => 'api',
            'admin' => 'admin',
        ];
    }
}
