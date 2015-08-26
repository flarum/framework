<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api;

class JsonApiRequest extends Request
{
    /**
     * @var array
     */
    public $sort;

    /**
     * @var array
     */
    public $include;

    /**
     * @var array
     */
    public $link;

    /**
     * @var int
     */
    public $limit;

    /**
     * @var int
     */
    public $offset;
}
