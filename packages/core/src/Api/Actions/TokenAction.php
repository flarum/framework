<?php namespace Flarum\Api\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Bus\Dispatcher;
use Flarum\Core\Commands\GenerateAccessTokenCommand;
use Flarum\Core\Repositories\UserRepositoryInterface;
use Flarum\Api\Actions\BaseAction;

class TokenAction extends BaseAction
{
    protected $users;

    public function __construct(UserRepositoryInterface $users, Dispatcher $bus)
    {
        $this->users = $users;
        $this->bus = $bus;
    }

    /**
     * Log in and return a token.
     *
     * @return Response
     */
    public function run(ApiParams $params)
    {
        $identification = $params->get('identification');
        $password = $params->get('password');

        $user = $this->users->findByIdentification($identification);

        if (! $user || ! $user->checkPassword($password)) {
            return $this->respondWithError('invalidCredentials', 401);
        }

        $command = new GenerateAccessTokenCommand($user->id);
        $token = $this->dispatch($command, $params);

        return new JsonResponse([
            'token' => $token->id,
            'userId' => $user->id
        ]);
    }
}
