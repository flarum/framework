<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Schema;

use Flarum\Api\Schema\Concerns\FlarumField;
use Tobyz\JsonApiServer\Schema\Field\Attribute as BaseAttribute;

class Attribute extends BaseAttribute
{
    use FlarumField;
}
