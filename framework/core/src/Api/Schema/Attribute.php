<?php

namespace Flarum\Api\Schema;

use Flarum\Api\Schema\Concerns\EvaluatesCallbacks;
use Flarum\Api\Schema\Concerns\HasValidationRules;
use Tobyz\JsonApiServer\Schema\Field\Attribute as BaseAttribute;

class Attribute extends BaseAttribute
{
    use HasValidationRules;
    use EvaluatesCallbacks;
}
