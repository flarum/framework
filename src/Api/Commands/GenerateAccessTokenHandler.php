<?php namespace Flarum\Api\Commands;

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
