<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Users\Commands;

use Flarum\Core\Settings\SettingsRepository;
use Flarum\Core\Users\PasswordToken;
use Flarum\Core\Users\UserRepository;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core;
use Flarum\Http\UrlGeneratorInterface;

class RequestPasswordResetHandler
{
    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var SettingsRepository
     */
    protected $settings;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var UrlGeneratorInterface
     */
    protected $url;

    /**
     * @param UserRepository $users
     * @param SettingsRepository $settings
     * @param Mailer $mailer
     * @param UrlGeneratorInterface $url
     */
    public function __construct(UserRepository $users, SettingsRepository $settings, Mailer $mailer, UrlGeneratorInterface $url)
    {
        $this->users = $users;
        $this->settings = $settings;
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
            'url' => Core::url().'/reset/'.$token->id,
            'forumTitle' => $this->settings->get('forum_title'),
        ];

        $this->mailer->send(['text' => 'flarum::emails.resetPassword'], $data, function (Message $message) use ($user) {
            $message->to($user->email);
            $message->subject('Reset Your Password');
        });

        return $user;
    }
}
