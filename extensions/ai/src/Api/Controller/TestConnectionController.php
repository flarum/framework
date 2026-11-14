<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Api\Controller;

use Closure;
use Flarum\Ai\AdminConnections;
use Flarum\Ai\AiConfig;
use Flarum\Ai\Connections\ConnectionTester;
use Flarum\Http\EventStream;
use Flarum\Http\RequestUtil;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Tests a connection, streaming its reply back as it's written
 * ({@see ConnectionTester}): a saved one by its name, or, given `connection`,
 * one the admin is adding or changing and hasn't saved yet.
 *
 * @internal
 */
class TestConnectionController implements RequestHandlerInterface
{
    public function __construct(
        protected AiConfig $config,
        protected AdminConnections $connections,
        protected ConnectionTester $tester,
        protected EventStream $stream
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $body = (array) $request->getParsedBody();
        $name = (string) Arr::get($body, 'name');

        if (array_key_exists('connection', $body)) {
            // Checked before the response starts, so a mistake is an ordinary error.
            $candidate = $this->connections->candidate($name, $body['connection']);
        } elseif (isset($this->config->connections()[$name])) {
            $candidate = null;
        } else {
            throw new ModelNotFoundException();
        }

        return $this->stream->response(fn (Closure $send) => $this->tester->run($name, $candidate, $send));
    }
}
