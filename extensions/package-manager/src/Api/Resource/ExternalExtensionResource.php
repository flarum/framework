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
use Flarum\Foundation\Application;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Pagination\OffsetPagination;
use Tobyz\JsonApiServer\Schema\CustomFilter;

class ExternalExtensionResource extends AbstractResource implements Listable, Paginatable, Countable
{
    protected int|null $totalResults = null;

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
            Schema\Str::make('discussUri')
                ->property('discuss_uri'),
            Schema\Str::make('vendor'),
            Schema\Boolean::make('isPremium')
                ->property('is_premium'),
            Schema\Boolean::make('isLocale')
                ->property('is_locale'),
            Schema\Str::make('locale'),
            Schema\Str::make('latestFlarumVersionSupported')
                ->property('latest_flarum_version_supported'),
            Schema\Boolean::make('compatibleWithLatestFlarum')
                ->property('compatible_with_latest_flarum'),
            Schema\Integer::make('downloads'),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
            SortColumn::make('downloads'),
        ];
    }

    public function filters(): array
    {
        return [
            CustomFilter::make('type', function (object $query, ?string $value) {
                if ($value) {
                    /** @var RequestWrapper $query */
                    $query->withQueryParams([
                        'filter' => [
                            'type' => $value,
                        ],
                    ]);
                }
            }),

            CustomFilter::make('is', function (object $query, null|string|array $value) {
                if ($value) {
                    /** @var RequestWrapper $query */
                    $query->withQueryParams([
                        'filter' => [
                            'is' => (array) $value,
                        ],
                    ]);
                }
            }),

            CustomFilter::make('q', function (object $query, ?string $value) {
                if ($value) {
                    /** @var RequestWrapper $query */
                    $query->withQueryParams([
                        'filter' => [
                            'q' => $value,
                        ],
                    ]);
                }
            }),
        ];
    }

    public function query(Context $context): object
    {
        return (new RequestWrapper($this->cache, 'https://flarum.org/api/extensions', 'GET', [
            'Accept' => 'application/json',
        ]))->withQueryParams([
            'filter' => [
                'compatible-with' => Application::VERSION,
            ],
        ]);
    }

    public function paginate(object $query, OffsetPagination $pagination): void
    {
        /** @var RequestWrapper $query */
        $query->withQueryParams([
            'page' => [
                'offset' => $pagination->offset,
                'limit' => $pagination->limit,
            ],
        ]);
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

        $this->totalResults = $json['meta']['page']['total'] ?? null;

        return (new Collection($json['data']))
            ->map(function (array $data) {
                $attributes = $data['attributes'];

                $attributes = array_combine(
                    array_map(fn ($key) => Str::snake(Str::camel($key)), array_keys($attributes)),
                    array_values($attributes)
                );

                return new Extension(array_merge([
                    'id' => $data['id'],
                ], $attributes));
            });
    }

    public function count(object $query, Context $context): ?int
    {
        return $this->totalResults;
    }
}
