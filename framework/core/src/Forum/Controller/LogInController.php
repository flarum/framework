<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Api\Client;
use Flarum\Forum\LogInValidator;
use Flarum\Http\AccessToken;
use Flarum\Http\RememberAccessToken;
use Flarum\Http\Rememberer;
use Flarum\Http\RequestUtil;
use Flarum\Http\SessionAuthenticator;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Flarum\User\Event\LoggedIn;
use Flarum\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class LogInController implements RequestHandlerInterface
{
    public function __construct(
        protected UserRepository $users,
        protected Client $apiClient,
        protected SessionAuthenticator $authenticator,
        protected Dispatcher $events,
        protected Rememberer $rememberer,
        protected LogInValidator $validator,
        protected UrlGenerator $url,
        protected TranslatorInterface $translator
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $params = Arr::only($body, ['identification', 'password', 'remember']);
        $isHtmlRequest = RequestUtil::isHtmlRequest($request);

        if ($isHtmlRequest) {
            $validator = $this->validator->basic()->prepare($body)->validator();

            if (! $validator->passes()) {
                $errors = $validator->errors();
                $request->getAttribute('session')->put('errors', $errors);

                return new RedirectResponse($this->url->to('forum')->route('maintenance.login'));
            }
        } else {
            $this->validator->assertValid($body);
        }

        $response = $this->apiClient->withParentRequest($request)->withBody($params)->post('/token');
        $rememberToken = null;

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody(), false);

            $token = AccessToken::findValid($data->token);

            $session = $request->getAttribute('session');
            $this->authenticator->logIn($session, $token);

            $this->events->dispatch(new LoggedIn($this->users->findOrFail($data->userId), $token));

            if ($token instanceof RememberAccessToken) {
                $rememberToken = $token;
            }
        }

        if ($isHtmlRequest) {
            if ($response->getStatusCode() === 401) {
                $errors = new MessageBag(['identification' => $this->translator->trans('core.views.log_in.invalid_login_message')]);
                $request->getAttribute('session')->put('errors', $errors);
            } elseif ($response->getStatusCode() === 429) {
                $errors = new MessageBag(['identification' => $this->translator->trans('core.views.log_in.too_many_attempts_message')]);
                $request->getAttribute('session')->put('errors', $errors);
            }

            $response = new RedirectResponse($this->url->to('forum')->route('maintenance.login'));
        }

        // Set the cookie on the response actually returned, which for the
        // log in page's form is the redirect rather than the API response.
        if ($rememberToken) {
            $response = $this->rememberer->remember($response, $rememberToken);
        }

        return $response;
    }
}
