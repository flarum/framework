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

use Flarum\Api\AccessToken;

class GenerateAccessTokenHandler
{
    /**
     * @param GenerateAccessToken $command
     * @return AccessToken
     */
    public function handle(GenerateAccessToken $command)
    {
        $token = AccessToken::generate($command->userId);

        $token->save();

        return $token;
    }
}
