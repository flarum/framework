<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource\Contracts;

use Tobyz\JsonApiServer\Context;

interface Countable extends Listable
{
    /**
     * Count the models for the given query.
     */
    public function count(object $query, Context $context): ?int;
}
