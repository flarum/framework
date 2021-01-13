<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration;

use Flarum\Extend\ExtenderInterface;
use Flarum\Foundation\Config;
use Flarum\Foundation\InstalledSite;
use Flarum\Foundation\Paths;
use Illuminate\Database\ConnectionInterface;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use BuildsHttpRequests;

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->database()->rollBack();
    }

    /**
     * @var \Flarum\Foundation\InstalledApp
     */
    protected $app;

    /**
     * @return \Flarum\Foundation\InstalledApp
     */
    protected function app()
    {
        if (is_null($this->app)) {
            $site = new InstalledSite(
                new Paths([
                    'base' => __DIR__.'/tmp',
                    'vendor' => __DIR__.'/../../vendor',
                    'public' => __DIR__.'/tmp/public',
                    'storage' => __DIR__.'/tmp/storage',
                ]),
                new Config(include __DIR__.'/tmp/config.php')
            );

            $site->extendWith($this->extenders);

            $this->app = $site->bootApp();

            $this->database()->beginTransaction();

            $this->populateDatabase();
        }

        return $this->app;
    }

    /**
     * @var ExtenderInterface[]
     */
    protected $extenders = [];

    protected function extend(ExtenderInterface ...$extenders)
    {
        $this->extenders = array_merge($this->extenders, $extenders);
    }

    /**
     * @var RequestHandlerInterface
     */
    protected $server;

    protected function server(): RequestHandlerInterface
    {
        if (is_null($this->server)) {
            $this->server = $this->app()->getRequestHandler();
        }

        return $this->server;
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

    protected $databaseContent = [];

    protected function prepareDatabase(array $tableData)
    {
        $this->databaseContent = array_merge_recursive(
            $this->databaseContent,
            $tableData
        );
    }

    protected function populateDatabase()
    {
        // We temporarily disable foreign key checks to simplify this process.
        $this->database()->getSchemaBuilder()->disableForeignKeyConstraints();

        // Then, insert all rows required for this test case.
        foreach ($this->databaseContent as $table => $rows) {
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
        return $this->server()->handle($request);
    }

    /**
     * Build a HTTP request that can be passed through middleware.
     *
     * This method simplifies building HTTP requests for use in our HTTP-level
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
     *   - "authenticatedAs" should identify an *existing* user by ID. This will
     *     cause an access token to be created for this user, which will be used
     *     to authenticate the request via the "Authorization" header.
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
            $request = $this->requestWithJsonBody(
                $request,
                $options['json']
            );
        }

        // Authenticate as a given user
        if (isset($options['authenticatedAs'])) {
            $request = $this->requestAsUser(
                $request,
                $options['authenticatedAs']
            );
        }

        // Let's copy the cookies from a previous response
        if (isset($options['cookiesFrom'])) {
            $request = $this->requestWithCookiesFrom(
                $request,
                $options['cookiesFrom']
            );
        }

        return $request;
    }
}
