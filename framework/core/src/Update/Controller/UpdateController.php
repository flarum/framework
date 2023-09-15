<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Update\Controller;

use Exception;
use Flarum\Database\Console\MigrateCommand;
use Flarum\Foundation\Config;
use Flarum\Http\Controller\AbstractController;
use Illuminate\Http\Request;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;

class UpdateController extends AbstractController
{
    public function __construct(
        protected MigrateCommand $command,
        protected Config $config
    ) {
    }

    public function __invoke(Request $request): ResponseInterface
    {
        if ($request->input('databasePassword') !== $this->config['database.password']) {
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
