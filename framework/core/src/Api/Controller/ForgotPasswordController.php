<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\User\Job\RequestPasswordResetJob;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ForgotPasswordController implements RequestHandlerInterface
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
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $email = Arr::get($request->getParsedBody(), 'email');

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

        return new EmptyResponse;
    }
}
