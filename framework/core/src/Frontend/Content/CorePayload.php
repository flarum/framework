<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Content;

use Flarum\Foundation\MaintenanceMode;
use Flarum\Frontend\Document;
use Flarum\Http\RequestUtil;
use Flarum\Locale\LocaleManager;
use Psr\Http\Message\ServerRequestInterface as Request;

class CorePayload
{
    public function __construct(
        private readonly LocaleManager $locales,
        private readonly MaintenanceMode $maintenance,
    ) {
    }

    public function __invoke(Document $document, Request $request): void
    {
        $document->payload = array_merge(
            $document->payload,
            $this->buildPayload($document, $request)
        );
    }

    private function buildPayload(Document $document, Request $request): array
    {
        $data = $this->getDataFromApiDocument($document->getForumApiDocument());

        $payload = [
            'resources' => $data,
            'session' => [
                'userId' => RequestUtil::getActor($request)->id,
                'csrfToken' => $request->getAttribute('session')->token()
            ],
            'locales' => $this->locales->getLocales(),
            'locale' => $request->getAttribute('locale'),
        ];

        if ($this->maintenance->inMaintenanceMode()) {
            $payload['maintenanceMode'] = $this->maintenance->mode();
        }

        return $payload;
    }

    private function getDataFromApiDocument(array $apiDocument): array
    {
        $data[] = $apiDocument['data'];

        if (isset($apiDocument['included'])) {
            $data = array_merge($data, $apiDocument['included']);
        }

        return $data;
    }
}
