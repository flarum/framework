<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Command;

use Flarum\User\Job\RequestPasswordResetJob;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\ValidationException;

class RequestPasswordResetHandler
{
    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var Factory
     */
    protected $validatorFactory;

    public function __construct(Queue $queue, Factory $validatorFactory)
    {
        $this->queue = $queue;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * @param RequestPasswordReset $command
     * @return void
     * @throws ValidationException
     */
    public function handle(RequestPasswordReset $command)
    {
        $email = $command->email;

        $validation = $this->validatorFactory->make(
            compact('email'),
            ['email' => 'required|email']
        );

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        // Prevents leaking user existence by not throwing an error.
        // Prevents leaking user existence by duration by using a queued job.
        $this->queue->push(new RequestPasswordResetJob($email));
    }
}
