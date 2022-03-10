<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Contracts\Types;

use PHPStan\Type\Type;

/**
 * @internal
 */
interface PassableContract
{
    /**
     * @return \PHPStan\Type\Type
     */
    public function getType(): Type;

    /**
     * @param  \PHPStan\Type\Type  $type
     * @return void
     */
    public function setType(Type $type): void;
}
