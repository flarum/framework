<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Console;

use Flarum\Console\AbstractCommand;
use Flarum\User\UserRepository;
use Flarum\User\UserValidator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

class ResetPasswordCommand extends AbstractCommand
{
    protected $questionHelper;

    protected $userRepository;

    protected $userValidator;

    /**
     * @param UserRepository $userRepository
     */
    public function __construct(QuestionHelper $questionHelper, UserRepository $userRepository, UserValidator $userValidator)
    {
        $this->questionHelper = $questionHelper;
        $this->userRepository = $userRepository;
        $this->userValidator = $userValidator;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('password:reset')
            ->setDescription('Reset a user\'s password');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $user = $this->getUser();
        $user->changePassword($this->askForPassword());
        $user->save();
    }

    private function getUser()
    {
        while (true) {
            $identification = $this->ask('Enter username or email:');
            $user = $this->userRepository->findByIdentification($identification);

            if ($user) {
                return $user;
            } else {
                $this->error('User with this username or email does not exist.');
                continue;
            }
        }
    }

    private function askForPassword()
    {
        while (true) {
            $password = $this->secret('New password (required >= 8 characters):');

            try {
                $this->userValidator->assertValid(compact('password'));
            } catch (ValidationException $e) {
                foreach ($e->errors()['password'] as $error) {
                    $this->error($error);
                }
                continue;
            }

            $confirmation = $this->secret('New password (confirmation):');

            if ($password !== $confirmation) {
                $this->error('The password did not match its confirmation.');
                continue;
            }

            return $password;
        }
    }
}
