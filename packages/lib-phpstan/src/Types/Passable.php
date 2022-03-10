<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Types;

use Flarum\PHPStan\Contracts\Types\PassableContract;
use PHPStan\Type\Type;

/**
 * @internal
 */
final class Passable implements PassableContract
{
    /**
     * @var \PHPStan\Type\Type
     */
    private $type;

    /**
     * Passable constructor.
     *
     * @param  \PHPStan\Type\Type  $type
     */
    public function __construct(Type $type)
    {
        $this->type = $type;
    }

    /**
     * @return \PHPStan\Type\Type
     */
    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * @param  \PHPStan\Type\Type  $type
     */
    public function setType(Type $type): void
    {
        $this->type = $type;
    }
}
