<?php namespace Flarum\Api\Actions;

use Flarum\Api\Commands\GenerateAccessToken;
use Flarum\Api\Request;
use Flarum\Core\Users\UserRepositoryInterface;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Core\Users\Events\UserEmailChangeWasRequested;
use Illuminate\Contracts\Bus\Dispatcher;
use Zend\Diactoros\Response\JsonResponse;

class TokenAction extends JsonApiAction
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
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface
     * @throws PermissionDeniedException
     */
    public function respond(Request $request)
    {
        $identification = $request->get('identification');
        $password = $request->get('password');

        $user = $this->users->findByIdentification($identification);

        if (! $user || ! $user->checkPassword($password)) {
            throw new PermissionDeniedException;
        }

        if (! $user->is_activated) {
            event(new UserEmailChangeWasRequested($user, $user->email));

            return new JsonResponse([
                'code' => 'confirm_email',
                'email' => $user->email
            ], 401);
        }

        $token = $this->bus->dispatch(
            new GenerateAccessToken($user->id)
        );

        return new JsonResponse([
            'token' => $token->id,
            'userId' => $user->id
        ]);
    }
}
