<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Schema\Relationship;

use Flarum\Api\Schema\Concerns\FlarumRelationship;
use Tobyz\JsonApiServer\Laravel\Field\ToMany as BaseToMany;

class ToMany extends BaseToMany
{
    use FlarumRelationship;
}
