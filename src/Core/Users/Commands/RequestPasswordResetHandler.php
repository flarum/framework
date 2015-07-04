<?php namespace Flarum\Core\Users\Commands;

use Flarum\Core\Users\PasswordToken;
use Flarum\Core\Users\UserRepositoryInterface;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core;
use Flarum\Http\UrlGeneratorInterface;

class RequestPasswordResetHandler
{
    /**
     * @var UserRepositoryInterface
     */
    protected $users;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @param UserRepositoryInterface $users
     * @param Mailer $mailer
     * @param UrlGeneratorInterface $url
     */
    public function __construct(UserRepositoryInterface $users, Mailer $mailer, UrlGeneratorInterface $url)
    {
        $this->users = $users;
        $this->mailer = $mailer;
        $this->url = $url;
    }

    /**
     * @param RequestPasswordReset $command
     * @return \Flarum\Core\Users\User
     * @throws ModelNotFoundException
     */
    public function handle(RequestPasswordReset $command)
    {
        $user = $this->users->findByEmail($command->email);

        if (! $user) {
            throw new ModelNotFoundException;
        }

        $token = PasswordToken::generate($user->id);
        $token->save();

        // TODO: Need to use UrlGenerator, but since this is part of core we
        // don't know that the forum routes will be loaded. Should the reset
        // password route be part of core??
        $data = [
            'username' => $user->username,
            'url' => Core::config('base_url').'/reset/'.$token->id,
            'forumTitle' => Core::config('forum_title')
        ];

        $this->mailer->send(['text' => 'flarum::emails.resetPassword'], $data, function (Message $message) use ($user) {
            $message->to($user->email);
            $message->subject('Reset Your Password');
        });

        return $user;
    }
}
