<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Endpoint;

use Flarum\Api\Context;
use Flarum\Api\Endpoint\Concerns\ExtractsListingParams;
use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Flarum\Api\Endpoint\Concerns\HasCustomHooks;
use Flarum\Api\Endpoint\Concerns\IncludesData;
use Flarum\Api\Endpoint\Concerns\ShowsResources;
use Flarum\Database\Eloquent\Collection;

class Show extends Endpoint
{
    use ShowsResources;
    use IncludesData;
    use HasAuthorization;
    use ExtractsListingParams;
    use HasCustomHooks;

    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'show');
    }

    public function setUp(): void
    {
        $this->route('GET', '/{id}')
            ->action(function (Context $context): ?object {
                $this->callBeforeHook($context);

                return $this->callAfterHook($context, $context->model);
            })
            ->beforeSerialization(function (Context $context, object $model) {
                $this->loadRelations(Collection::make([$model]), $context, $this->getInclude($context));
            });
    }
}
