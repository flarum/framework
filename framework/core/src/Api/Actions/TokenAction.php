<?php namespace Flarum\Api\Actions;

use Flarum\Api\Request;
use Flarum\Core\Commands\GenerateAccessTokenCommand;
use Flarum\Core\Repositories\UserRepositoryInterface;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Bus\Dispatcher;

class TokenAction implements ActionInterface
{
    protected $users;

    protected $bus;

    public function __construct(UserRepositoryInterface $users, Dispatcher $bus)
    {
        $this->users = $users;
        $this->bus = $bus;
    }

    /**
     * Log in and return a token.
     *
     * @param \Flarum\Api\Request $request
     * @return \Flarum\Api\Response
     */
    public function handle(Request $request)
    {
        $identification = $request->get('identification');
        $password = $request->get('password');

        $user = $this->users->findByIdentification($identification);

        if (! $user || ! $user->checkPassword($password)) {
            // throw new PermissionDeniedException;
            return new JsonResponse(null, 401);
        }

        $token = $this->bus->dispatch(
            new GenerateAccessTokenCommand($user->id)
        );

        return new JsonResponse([
            'token' => $token->id,
            'userId' => $user->id
        ]);
    }
}
