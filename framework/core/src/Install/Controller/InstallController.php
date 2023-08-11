<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Controller;

use Flarum\Http\Controller\AbstractController;
use Flarum\Http\RememberAccessToken;
use Flarum\Http\Rememberer;
use Flarum\Http\SessionAuthenticator;
use Flarum\Install\AdminUser;
use Flarum\Install\BaseUrl;
use Flarum\Install\DatabaseConfig;
use Flarum\Install\Installation;
use Flarum\Install\StepFailed;
use Flarum\Install\ValidationFailed;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laminas\Diactoros\Response as PsrResponse;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class InstallController extends AbstractController
{
    public function __construct(
        protected Installation $installation,
        protected SessionAuthenticator $authenticator,
        protected Rememberer $rememberer
    ) {
    }

    public function __invoke(Request $request): ResponseInterface|Response
    {
        $input = $request->input();
        $baseUrl = BaseUrl::fromString($request->getUri());

        // An access token we will use to auto-login the admin at the end of installation
        $accessToken = Str::random(40);

        try {
            $pipeline = $this->installation
                ->baseUrl($baseUrl)
                ->databaseConfig($this->makeDatabaseConfig($input))
                ->adminUser($this->makeAdminUser($input))
                ->accessToken($accessToken)
                ->settings([
                    'forum_title' => Arr::get($input, 'forumTitle'),
                    'mail_from' => $baseUrl->toEmail('noreply'),
                    'welcome_title' => 'Welcome to '.Arr::get($input, 'forumTitle'),
                ])
                ->build();
        } catch (ValidationFailed $e) {
            return new PsrResponse\HtmlResponse($e->getMessage(), 500);
        }

        try {
            $pipeline->run();
        } catch (StepFailed $e) {
            return new PsrResponse\HtmlResponse($e->getPrevious()->getMessage(), 500);
        }

        $session = $request->attributes->get('session');

        // Because the Eloquent models cannot be used yet, we create a temporary in-memory object
        // that won't interact with the database but can be passed to the authenticator and rememberer
        $token = new RememberAccessToken();
        $token->token = $accessToken;
        $this->authenticator->logIn($session, $token);

        return $this->rememberer->remember(new Response(null, 204), $token);
    }

    private function makeDatabaseConfig(array $input): DatabaseConfig
    {
        $host = Arr::get($input, 'mysqlHost');
        $port = 3306;

        if (Str::contains($host, ':')) {
            list($host, $port) = explode(':', $host, 2);
        }

        return new DatabaseConfig(
            'mysql',
            $host,
            intval($port),
            Arr::get($input, 'mysqlDatabase'),
            Arr::get($input, 'mysqlUsername'),
            Arr::get($input, 'mysqlPassword'),
            Arr::get($input, 'tablePrefix')
        );
    }

    /**
     * @throws ValidationFailed
     */
    private function makeAdminUser(array $input): AdminUser
    {
        return new AdminUser(
            Arr::get($input, 'adminUsername'),
            $this->getConfirmedAdminPassword($input),
            Arr::get($input, 'adminEmail')
        );
    }

    private function getConfirmedAdminPassword(array $input): string
    {
        $password = Arr::get($input, 'adminPassword');
        $confirmation = Arr::get($input, 'adminPasswordConfirmation');

        if ($password !== $confirmation) {
            throw new ValidationFailed('The admin password did not match its confirmation.');
        }

        return $password;
    }
}
