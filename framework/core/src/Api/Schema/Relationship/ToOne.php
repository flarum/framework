<?php

namespace Flarum\Api\Schema\Relationship;

use Flarum\Api\Schema\Concerns\EvaluatesCallbacks;
use Flarum\Api\Schema\Concerns\HasValidationRules;
use Tobyz\JsonApiServer\Schema\Field\ToOne as BaseToOne;

class ToOne extends BaseToOne
{
    use HasValidationRules;
    use EvaluatesCallbacks;
}
