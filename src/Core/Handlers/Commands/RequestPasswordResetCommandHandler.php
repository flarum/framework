<?php namespace Flarum\Core\Handlers\Commands;

use Flarum\Core\Commands\RequestPasswordResetCommand;
use Flarum\Core\Models\PasswordToken;
use Flarum\Core\Repositories\UserRepositoryInterface;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core;

class RequestPasswordResetCommandHandler
{
    /**
     * @var UserRepositoryInterface
     */
    protected $users;

    /**
     * The mailer instance.
     *
     * @var \Illuminate\Contracts\Mail\Mailer
     */
    protected $mailer;

    public function __construct(UserRepositoryInterface $users, Mailer $mailer)
    {
        $this->users = $users;
        $this->mailer = $mailer;
    }

    public function handle(RequestPasswordResetCommand $command)
    {
        $user = $this->users->findByEmail($command->email);

        if (! $user) {
            throw new ModelNotFoundException;
        }

        $token = PasswordToken::generate($user->id);
        $token->save();

        $data = [
            'username' => $user->username,
            'url' => route('flarum.forum.resetPassword', ['token' => $token->id]),
            'forumTitle' => Core::config('forum_title')
        ];

        $this->mailer->send(['text' => 'flarum::emails.resetPassword'], $data, function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Reset Your Password');
        });

        return $user;
    }
}
