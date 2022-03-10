<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Types\ModelProperty;

use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class ModelPropertyType extends StringType
{
    /**
     * @param  mixed[]  $properties
     * @return Type
     */
    public static function __set_state(array $properties): Type
    {
        return new self();
    }
}
