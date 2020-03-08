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
                $this->validationError('User with this username or email does not exist.');
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
                    $this->validationError($error);
                }
                continue;
            }

            $confirmation = $this->secret('New password (confirmation):');

            if ($password !== $confirmation) {
                $this->validationError('The password did not match its confirmation.');
                continue;
            }

            return $password;
        }
    }

    private function ask($question, $default = null)
    {
        $question = new Question("<question>$question</question> ", $default);

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    private function secret($question)
    {
        $question = new Question("<question>$question</question> ");

        $question->setHidden(true)->setHiddenFallback(true);

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    private function validationError($message)
    {
        $this->output->writeln("<error>$message</error>");
        $this->output->writeln('Please try again.');
    }
}
