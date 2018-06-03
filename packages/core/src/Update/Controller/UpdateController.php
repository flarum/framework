<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Update\Controller;

use Exception;
use Flarum\Database\Console\MigrateCommand;
use Flarum\Foundation\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;

class UpdateController implements RequestHandlerInterface
{
    protected $command;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param MigrateCommand $command
     * @param Application $app
     */
    public function __construct(MigrateCommand $command, Application $app)
    {
        $this->command = $command;
        $this->app = $app;
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     */
    public function handle(Request $request): ResponseInterface
    {
        $input = $request->getParsedBody();

        if (array_get($input, 'databasePassword') !== $this->app->config('database.password')) {
            return new HtmlResponse('Incorrect database password.', 500);
        }

        $body = fopen('php://temp', 'wb+');
        $input = new StringInput('');
        $output = new StreamOutput($body);

        try {
            $this->command->run($input, $output);
        } catch (Exception $e) {
            return new HtmlResponse($e->getMessage(), 500);
        }

        return new Response($body, 200);
    }
}
