<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\ForgotPasswordValidator;
use Flarum\Http\Controller\AbstractController;
use Flarum\User\Job\RequestPasswordResetJob;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Http\Request;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;

class ForgotPasswordController extends AbstractController
{
    public function __construct(
        protected Queue $queue,
        protected ForgotPasswordValidator $validator
    ) {
    }

    public function __invoke(Request $request): ResponseInterface
    {
        $this->validator->assertValid(
            $request->json()->all()
        );

        $email = $request->json('email');

        // Prevents leaking user existence by not throwing an error.
        // Prevents leaking user existence by duration by using a queued job.
        $this->queue->push(new RequestPasswordResetJob($email));

        return new EmptyResponse;
    }
}
