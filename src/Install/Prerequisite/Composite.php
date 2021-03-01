<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Prerequisite;

use Illuminate\Support\Collection;

class Composite implements PrerequisiteInterface
{
    /**
     * @var PrerequisiteInterface[]
     */
    protected $prerequisites = [];

    public function __construct(PrerequisiteInterface $first)
    {
        foreach (func_get_args() as $prerequisite) {
            $this->prerequisites[] = $prerequisite;
        }
    }

    public function problems(): Collection
    {
        return array_reduce(
            $this->prerequisites,
            function (Collection $errors, PrerequisiteInterface $condition) {
                return $errors->concat($condition->problems());
            },
            new Collection
        );
    }
}
