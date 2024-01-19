<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Database\AbstractModel;
use Flarum\User\User;

/**
 * @template T of AbstractModel
 */
interface SlugDriverInterface
{
    /**
     * @param T $instance
     */
    public function toSlug(AbstractModel $instance): string;

    /**
     * @return T
     */
    public function fromSlug(string $slug, User $actor): AbstractModel;
}
