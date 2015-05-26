<?php namespace Flarum\Api\Actions\Users;

use Flarum\Api\Request;
use Flarum\Api\Actions\JsonApiAction;
use Flarum\Core\Repositories\UserRepositoryInterface;
use Flarum\Core\Commands\RequestPasswordResetCommand;
use Illuminate\Http\Response;
use Illuminate\Contracts\Bus\Dispatcher;

class ForgotAction extends JsonApiAction
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
    public function respond(Request $request)
    {
        $email = $request->get('email');

        $this->bus->dispatch(
            new RequestPasswordResetCommand($email)
        );

        return new Response;
    }
}
