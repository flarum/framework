<?php

namespace Flarum\Api\Schema\Relationship;

use Flarum\Api\Schema\Concerns\EvaluatesCallbacks;
use Flarum\Api\Schema\Concerns\HasValidationRules;
use Tobyz\JsonApiServer\Schema\Field\ToMany as BaseToMany;

class ToMany extends BaseToMany
{
    use HasValidationRules;
    use EvaluatesCallbacks;
}
