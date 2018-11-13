<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Controller;

use Flarum\Http\SessionAuthenticator;
use Flarum\Install\Installation;
use Flarum\Install\StepFailed;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;

class InstallController implements RequestHandlerInterface
{
    /**
     * @var Installation
     */
    protected $installation;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * InstallController constructor.
     * @param Installation $installation
     * @param SessionAuthenticator $authenticator
     */
    public function __construct(Installation $installation, SessionAuthenticator $authenticator)
    {
        $this->installation = $installation;
        $this->authenticator = $authenticator;
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     */
    public function handle(Request $request): ResponseInterface
    {
        $input = $request->getParsedBody();

        $host = array_get($input, 'mysqlHost');
        $port = '3306';

        if (str_contains($host, ':')) {
            list($host, $port) = explode(':', $host, 2);
        }

        $baseUrl = rtrim((string) $request->getUri(), '/');

        $pipeline = $this->installation
            ->baseUrl($baseUrl)
            ->databaseConfig([
                'driver' => 'mysql',
                'host' => $host,
                'port' => $port,
                'database' => array_get($input, 'mysqlDatabase'),
                'username' => array_get($input, 'mysqlUsername'),
                'password' => array_get($input, 'mysqlPassword'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => array_get($input, 'tablePrefix'),
                'strict' => false,
            ])
            ->adminUser([
                'username' => array_get($input, 'adminUsername'),
                'password' => array_get($input, 'adminPassword'),
                'password_confirmation' => array_get($input, 'adminPasswordConfirmation'),
                'email' => array_get($input, 'adminEmail'),
            ])
            ->settings([
                'allow_post_editing' => 'reply',
                'allow_renaming' => '10',
                'allow_sign_up' => '1',
                'custom_less' => '',
                'default_locale' => 'en',
                'default_route' => '/all',
                'extensions_enabled' => '[]',
                'forum_title' => array_get($input, 'forumTitle'),
                'forum_description' => '',
                'mail_driver' => 'mail',
                'mail_from' => 'noreply@'.preg_replace('/^www\./i', '', parse_url($baseUrl, PHP_URL_HOST)),
                'theme_colored_header' => '0',
                'theme_dark_mode' => '0',
                'theme_primary_color' => '#4D698E',
                'theme_secondary_color' => '#4D698E',
                'welcome_message' => 'This is beta software and you should not use it in production.',
                'welcome_title' => 'Welcome to '.array_get($input, 'forumTitle'),
            ])
            ->build();

        try {
            $pipeline->run();
        } catch (StepFailed $e) {
            return new Response\HtmlResponse($e->getPrevious()->getMessage(), 500);
        }

        $session = $request->getAttribute('session');
        $this->authenticator->logIn($session, 1);

        return new Response\EmptyResponse;
    }
}
