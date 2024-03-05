<?php

namespace Flarum\Api\Endpoint;

use Flarum\Api\Context;
use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Flarum\Api\Endpoint\Concerns\HasCustomHooks;
use Flarum\Api\Endpoint\Concerns\HasEagerLoading;
use Illuminate\Database\Eloquent\Collection;
use Tobyz\JsonApiServer\Endpoint\Create as BaseCreate;

class Create extends BaseCreate implements EndpointInterface
{
    use HasAuthorization;
    use HasEagerLoading;
    use HasCustomHooks;

    public function setUp(): void
    {
        parent::setUp();

        $this->after(function (Context $context, object $model) {
            $this->loadRelations(Collection::make([$model]), $context, $this->getInclude($context));

            return $model;
        });
    }
}
