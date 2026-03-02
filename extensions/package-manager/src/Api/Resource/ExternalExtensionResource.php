<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Resource;

use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractResource;
use Flarum\Api\Resource\Contracts\Countable;
use Flarum\Api\Resource\Contracts\Listable;
use Flarum\Api\Resource\Contracts\Paginatable;
use Flarum\Api\Schema;
use Flarum\ExtensionManager\Api\Schema\SortColumn;
use Flarum\ExtensionManager\Exception\CannotFetchExternalExtension;
use Flarum\ExtensionManager\External\Extension;
use Flarum\ExtensionManager\External\RequestWrapper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Pagination\OffsetPagination;
use Tobyz\JsonApiServer\Schema\CustomFilter;

class ExternalExtensionResource extends AbstractResource implements Listable, Paginatable, Countable
{
    /**
     * Packagist search API.
     * Docs: https://packagist.org/apidoc#search-packages-by-type.
     */
    protected const PACKAGIST_SEARCH_URL = 'https://packagist.org/search.json';

    /**
     * All Flarum packages use the 'flarum-extension' Packagist type.
     * Language packs and themes cannot be filtered by type; instead we narrow via a search query.
     * Maps frontend filter[type] value → ['param' => 'type'|'q', 'value' => string].
     */
    protected const TYPE_FILTER_MAP = [
        // 'extension' tab: keep default type, no extra param needed
        'locale' => ['param' => 'q',    'value' => 'flarum-lang'],
        'theme' => ['param' => 'q',    'value' => 'flarum theme'],
    ];

    protected ?int $totalResults = null;

    public function __construct(
        protected Repository $cache,
    ) {
    }

    public function type(): string
    {
        return 'external-extensions';
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->authenticated()
                ->admin()
                ->paginate(12, 20),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('extensionId')
                ->get(fn (Extension $extension) => $extension->extensionId()),
            Schema\Str::make('name'),
            Schema\Str::make('title'),
            Schema\Str::make('description'),
            Schema\Str::make('iconUrl')
                ->property('icon_url'),
            Schema\Arr::make('icon'),
            Schema\Str::make('highestVersion')
                ->property('highest_version'),
            Schema\Str::make('httpUri')
                ->property('http_uri'),
            Schema\Str::make('vendor'),
            Schema\Boolean::make('isLocale')
                ->property('is_locale'),
            Schema\Integer::make('downloads'),
            Schema\Boolean::make('abandoned'),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('downloads'),
        ];
    }

    public function filters(): array
    {
        return [
            CustomFilter::make('type', function (object $query, ?string $value) {
                /** @var RequestWrapper $query */
                if (! $value || ! isset(static::TYPE_FILTER_MAP[$value])) {
                    // 'extension' tab or unknown: keep default type=flarum-extension from query().
                    return;
                }

                $filter = static::TYPE_FILTER_MAP[$value];
                $query->setQueryParam($filter['param'], $filter['value']);
            }),

            CustomFilter::make('q', function (object $query, ?string $value) {
                if ($value) {
                    /** @var RequestWrapper $query */
                    $query->setQueryParam('q', $value);
                }
            }),
        ];
    }

    public function query(Context $context): object
    {
        return (new RequestWrapper($this->cache, static::PACKAGIST_SEARCH_URL, 'GET', [
            'Accept' => 'application/json',
        ]))->setQueryParam('type', 'flarum-extension');
    }

    public function paginate(object $query, OffsetPagination $pagination): void
    {
        /** @var RequestWrapper $query */
        $query->setQueryParam('page', (int) floor($pagination->offset / $pagination->limit) + 1);
        $query->setQueryParam('per_page', $pagination->limit);
    }

    public function results(object $query, Context $context): iterable
    {
        /** @var RequestWrapper $query */
        $json = $query->cache(function (RequestWrapper $query) {
            try {
                $response = (new Client())->send($query->getRequest());
            } catch (GuzzleException) {
                throw new CannotFetchExternalExtension();
            }

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                throw new CannotFetchExternalExtension();
            }

            return json_decode($response->getBody()->getContents(), true);
        });

        $this->totalResults = $json['total'] ?? null;

        return (new Collection($json['results'] ?? []))
            ->map(function (array $data) {
                $nameParts = explode('/', $data['name'], 2);

                return new Extension([
                    'id' => $data['name'],
                    'name' => $data['name'],
                    'title' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'vendor' => $nameParts[0] ?? null,
                    'http_uri' => $data['url'] ?? null,
                    'icon_url' => null,
                    'icon' => null,
                    'downloads' => (int) ($data['downloads'] ?? 0),
                    'abandoned' => ! empty($data['abandoned']),
                    'is_locale' => false,
                    'highest_version' => null,
                ]);
            });
    }

    public function count(object $query, Context $context): ?int
    {
        return $this->totalResults;
    }
}
