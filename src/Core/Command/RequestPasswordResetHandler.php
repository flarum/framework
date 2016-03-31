<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Command;

use Flarum\Core;
use Flarum\Core\Exception\ValidationException;
use Flarum\Core\PasswordToken;
use Flarum\Core\Repository\UserRepository;
use Flarum\Forum\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Mail\Message;
use Symfony\Component\Translation\TranslatorInterface;

class RequestPasswordResetHandler
{
    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var UserValidator
     */
    protected $validator;

    /**
     * @var Factory
     */
    private $validatorFactory;

    /**
     * @param UserRepository $users
     * @param SettingsRepositoryInterface $settings
     * @param Mailer $mailer
     * @param UrlGenerator $url
     * @param TranslatorInterface $translator
     */
    public function __construct(UserRepository $users, SettingsRepositoryInterface $settings, Mailer $mailer, UrlGenerator $url, TranslatorInterface $translator, UserValidator $validator, Factory $validatorFactory)
    {
        $this->users = $users;
        $this->settings = $settings;
        $this->mailer = $mailer;
        $this->url = $url;
        $this->translator = $translator;
        $this->validator = $validator;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * @param RequestPasswordReset $command
     * @return \Flarum\Core\User
     * @throws ModelNotFoundException
     */
    public function handle(RequestPasswordReset $command)
    {
        $validation = $this->validatorFactory->make(['email' => $command->email], ['email' => 'required|email']);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $user = $this->users->findByEmail($command->email);

        if (! $user) {
            throw new ModelNotFoundException;
        }

        $token = PasswordToken::generate($user->id);
        $token->save();

        $data = [
            '{username}' => $user->username,
            '{url}' => $this->url->toRoute('resetPassword', ['token' => $token->id]),
            '{forum}' => $this->settings->get('forum_title'),
        ];

        $body = $this->translator->trans('core.email.reset_password.body', $data);

        $this->mailer->raw($body, function (Message $message) use ($user, $data) {
            $message->to($user->email);
            $message->subject('['.$data['{forum}'].'] '.$this->translator->trans('core.email.reset_password.subject'));
        });

        return $user;
    }
}
