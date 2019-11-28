<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration;

use Dflydev\FigCookies\SetCookie;
use Flarum\Foundation\InstalledSite;
use Illuminate\Database\ConnectionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\CallbackStream;
use Zend\Diactoros\ServerRequest;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Boot the Flarum app
        $this->app();
    }

    /**
     * @var \Flarum\Foundation\InstalledApp
     */
    protected $app;

    /**
     * @var \Psr\Http\Server\RequestHandlerInterface
     */
    protected $server;

    /**
     * @return \Flarum\Foundation\InstalledApp
     */
    protected function app()
    {
        if (is_null($this->app)) {
            $site = new InstalledSite(
                [
                    'base' => __DIR__.'/tmp',
                    'vendor' => __DIR__.'/../../vendor',
                    'public' => __DIR__.'/tmp/public',
                    'storage' => __DIR__.'/tmp/storage',
                ],
                include __DIR__.'/tmp/config.php'
            );

            $this->app = $site->bootApp();
            $this->server = $this->app->getRequestHandler();
        }

        return $this->app;
    }

    protected $database;

    protected function database(): ConnectionInterface
    {
        if (is_null($this->database)) {
            $this->database = $this->app()->getContainer()->make(
                ConnectionInterface::class
            );
        }

        return $this->database;
    }

    protected function prepareDatabase(array $tableData)
    {
        // We temporarily disable foreign key checks to simplify this process.
        $this->database()->getSchemaBuilder()->disableForeignKeyConstraints();

        // First, truncate all referenced tables so that they are empty.
        foreach (array_keys($tableData) as $table) {
            if ($table !== 'settings') {
                $this->database()->table($table)->truncate();
            }
        }

        // Then, insert all rows required for this test case.
        foreach ($tableData as $table => $rows) {
            foreach ($rows as $row) {
                if ($table === 'settings') {
                    $this->database()->table($table)->updateOrInsert(
                        ['key' => $row['key']],
                        $row
                    );
                } else {
                    $this->database()->table($table)->updateOrInsert(
                        isset($row['id']) ? ['id' => $row['id']] : $row,
                        $row
                    );
                }
            }
        }

        // And finally, turn on foreign key checks again.
        $this->database()->getSchemaBuilder()->enableForeignKeyConstraints();
    }

    /**
     * Send a full HTTP request through Flarum's middleware stack.
     */
    protected function send(ServerRequestInterface $request): ResponseInterface
    {
        return $this->server->handle($request);
    }

    /**
     * Build a HTTP request that can be passed through middleware.
     *
     * This method simplifies building HTTP request for use in our HTTP-level
     * integration tests. It provides options for all features repeatedly being
     * used in those tests.
     *
     * @param string $method
     * @param string $path
     * @param array $options
     *   An array of optional request properties.
     *   Currently supported:
     *   - "json" should point to a JSON-serializable object that will be
     *     serialized and used as request body. The corresponding Content-Type
     *     header will be set automatically.
     *   - "cookiesFrom" should hold a response object from a previous HTTP
     *     interaction. All cookies returned from the server in that response
     *     (via the "Set-Cookie" header) will be copied to the cookie params of
     *     the new request.
     * @return ServerRequestInterface
     */
    protected function request(string $method, string $path, array $options = []): ServerRequestInterface
    {
        $request = new ServerRequest([], [], $path, $method);

        // Do we want a JSON request body?
        if (isset($options['json'])) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody(
                    new CallbackStream(function () use ($options) {
                        return json_encode($options['json']);
                    })
                );
        }

        // Let's copy the cookies from a previous response
        if (isset($options['cookiesFrom'])) {
            /** @var ResponseInterface $previousResponse */
            $previousResponse = $options['cookiesFrom'];

            $cookies = array_reduce(
                $previousResponse->getHeader('Set-Cookie'),
                function ($memo, $setCookieString) {
                    $setCookie = SetCookie::fromSetCookieString($setCookieString);
                    $memo[$setCookie->getName()] = $setCookie->getValue();

                    return $memo;
                },
                []
            );

            $request = $request->withCookieParams($cookies);
        }

        return $request;
    }
}
