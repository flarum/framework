<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Command;

use Flarum\User\Job\RequestPasswordResetJob;
use Flarum\User\UserRepository;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Foundation\ValidationException;
use Symfony\Contracts\Translation\TranslatorInterface;

class RequestPasswordResetHandler
{
    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Factory
     */
    protected $validatorFactory;

    public function __construct(
        UserRepository $users,
        Queue $queue,
        TranslatorInterface $translator,
        Factory $validatorFactory
    ) {
        $this->users = $users;
        $this->queue = $queue;
        $this->translator = $translator;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * @param RequestPasswordReset $command
     * @return \Flarum\User\User
     * @throws ValidationException
     */
    public function handle(RequestPasswordReset $command)
    {
        $email = $command->email;

        $validation = $this->validatorFactory->make(
            compact('email'),
            ['email' => 'required|email']
        );

        $user = $this->users->findByEmail($email);

        // Prevents leaking user existence.
        if ($validation->fails() || ! $user) {
            throw new ValidationException([
                'message' => strtr($this->translator->trans('validation.email'), [
                    ':attribute' => $this->translator->trans('validation.attributes.email')
                ])
            ]);
        }

        // Prevents leak user existence by duration (requires a queue).
        $this->queue->push(new RequestPasswordResetJob($user));

        return $user;
    }
}
