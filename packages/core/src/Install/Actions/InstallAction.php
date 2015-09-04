<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Actions;

use Flarum\Support\Action;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response;
use Flarum\Install\Console\InstallCommand;
use Flarum\Install\Console\DefaultData;
use Flarum\Api\Commands\GenerateAccessToken;
use Flarum\Forum\Actions\WritesRememberCookie;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Input\StringInput;
use Illuminate\Contracts\Bus\Dispatcher;
use Exception;
use DateTime;

class InstallAction extends Action
{
    use WritesRememberCookie;

    protected $command;

    /**
     * @var Dispatcher
     */
    protected $bus;

    public function __construct(InstallCommand $command, Dispatcher $bus)
    {
        $this->command = $command;
        $this->bus = $bus;
    }

    /**
     * @param Request $request
     * @param array $routeParams
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(Request $request, array $routeParams = [])
    {
        $input = $request->getParsedBody();

        $data = new DefaultData;

        $data->setDatabaseConfiguration([
            'driver'   => 'mysql',
            'host'     => array_get($input, 'mysqlHost'),
            'database' => array_get($input, 'mysqlDatabase'),
            'username' => array_get($input, 'mysqlUsername'),
            'password' => array_get($input, 'mysqlPassword'),
            'prefix'   => array_get($input, 'tablePrefix'),
        ]);

        $data->setAdminUser([
            'username'              => array_get($input, 'adminUsername'),
            'password'              => array_get($input, 'adminPassword'),
            'password_confirmation' => array_get($input, 'adminPasswordConfirmation'),
            'email'                 => array_get($input, 'adminEmail'),
        ]);

        $baseUrl = rtrim((string) $request->getAttribute('originalUri'), '/');
        $data->setBaseUrl($baseUrl);

        $data->setSetting('forum_title', array_get($input, 'forumTitle'));
        $data->setSetting('mail_from', 'noreply@' . preg_replace('/^www\./i', '', parse_url($baseUrl, PHP_URL_HOST)));
        $data->setSetting('welcome_title', 'Welcome to ' . array_get($input, 'forumTitle'));

        $body = fopen('php://temp', 'wb+');
        $input = new StringInput('');
        $output = new StreamOutput($body);

        $this->command->setDataSource($data);

        try {
            $this->command->run($input, $output);
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], 500);
        }

        $token = $this->bus->dispatch(
            new GenerateAccessToken(1)
        );
        $token->update(['expires_at' => new DateTime('+2 weeks')]);

        return $this->withRememberCookie(
            new Response($body, 200),
            $token->id
        );
    }
}
