<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Foundation\DispatchEventsTrait;
use Flarum\Http\SessionAuthenticator;
use Flarum\Http\UrlGenerator;
use Flarum\User\PasswordToken;
use Flarum\User\UserValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\RedirectResponse;

class SavePasswordController implements RequestHandlerInterface
{
    use DispatchEventsTrait;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var \Flarum\User\UserValidator
     */
    protected $validator;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Factory
     */
    protected $validatorFactory;

    /**
     * @param UrlGenerator $url
     * @param SessionAuthenticator $authenticator
     * @param UserValidator $validator
     * @param Factory $validatorFactory
     */
    public function __construct(UrlGenerator $url, SessionAuthenticator $authenticator, UserValidator $validator, Factory $validatorFactory, Dispatcher $events)
    {
        $this->url = $url;
        $this->authenticator = $authenticator;
        $this->validator = $validator;
        $this->validatorFactory = $validatorFactory;
        $this->events = $events;
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     */
    public function handle(Request $request): ResponseInterface
    {
        $input = $request->getParsedBody();

        $token = PasswordToken::findOrFail(array_get($input, 'passwordToken'));

        $password = array_get($input, 'password');

        try {
            // todo: probably shouldn't use the user validator for this,
            // passwords should be validated separately
            $this->validator->assertValid(compact('password'));

            $validator = $this->validatorFactory->make($input, ['password' => 'required|confirmed']);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        } catch (ValidationException $e) {
            $request->getAttribute('session')->put('errors', $e->errors());

            return new RedirectResponse($this->url->to('forum')->route('resetPassword', ['token' => $token->id]));
        }

        $token->user->changePassword($password);
        $token->user->save();

        $this->dispatchEventsFor($token->user);

        $token->delete();

        $session = $request->getAttribute('session');
        $this->authenticator->logIn($session, $token->user->id);

        return new RedirectResponse($this->url->to('forum')->base());
    }
}
