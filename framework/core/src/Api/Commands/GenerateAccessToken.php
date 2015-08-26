<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Commands;

class GenerateAccessToken
{
    /**
     * The ID of the user to generate an access token for.
     *
     * @var int
     */
    public $userId;

    /**
     * @param int $userId The ID of the user to generate an access token for.
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
    }
}
