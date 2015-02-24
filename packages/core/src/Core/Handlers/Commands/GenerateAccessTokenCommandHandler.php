<?php namespace Flarum\Core\Handlers\Commands;

use Flarum\Core\Models\AccessToken;

class GenerateAccessTokenCommandHandler
{
    public function handle($command)
    {
        $token = AccessToken::generate($command->userId);
        $token->save();

        return $token;
    }
}
