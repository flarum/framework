<?php

namespace Flarum\Api\Endpoint;

use Flarum\Api\Context;
use Flarum\Api\Endpoint\Concerns\ExtractsListingParams;
use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Flarum\Api\Endpoint\Concerns\HasCustomHooks;
use Flarum\Api\Endpoint\Concerns\HasEagerLoading;
use Illuminate\Database\Eloquent\Collection;
use Tobyz\JsonApiServer\Endpoint\Show as BaseShow;

class Show extends BaseShow implements EndpointInterface
{
    use HasAuthorization;
    use HasEagerLoading;
    use ExtractsListingParams;
    use HasCustomHooks;

    public function setUp(): void
    {
        parent::setUp();

        $this->beforeSerialization(function (Context $context, object $model) {
            $this->loadRelations(Collection::make([$model]), $context, $this->getInclude($context));
        });
    }
}
