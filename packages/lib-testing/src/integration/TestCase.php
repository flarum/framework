<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\integration;

use Flarum\Extend\ExtenderInterface;
use Flarum\Foundation\Config;
use Flarum\Foundation\InstalledSite;
use Flarum\Foundation\Paths;
use Flarum\Testing\integration\Extend\BeginTransactionAndSetDatabase;
use Flarum\Testing\integration\Extend\OverrideExtensionManagerForTests;
use Flarum\Testing\integration\Extend\SetSettingsBeforeBoot;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use BuildsHttpRequests;
    use UsesTmpDir;

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
            $tmp = $this->tmpDir();

            $config = include "$tmp/config.php";

            foreach ($this->config as $key => $value) {
                Arr::set($config, $key, $value);
            }

            $site = new InstalledSite(
                new Paths([
                    'base' => $tmp,
                    'public' => "$tmp/public",
                    'storage' => "$tmp/storage",
                    'vendor' => getcwd().'/vendor',
                ]),
                new Config($config)
            );

            $extenders = array_merge([
                new OverrideExtensionManagerForTests($this->extensions),
                new BeginTransactionAndSetDatabase(function (ConnectionInterface $db) {
                    $this->database = $db;
                }),
                new SetSettingsBeforeBoot($this->settings),
            ], $this->extenders);

            $site->extendWith($extenders);

            $this->app = $site->bootApp();

            $this->populateDatabase();
        }

        return $this->app;
    }

    /**
     * @var ExtenderInterface[]
     */
    protected $extenders = [];

    /**
     * Each argument should be an instance of an extender that should
     * be applied at application boot.
     * 
     * Note that this method will have no effect if called after the
     * application is booted.
     */
    protected function extend(ExtenderInterface ...$extenders)
    {
        $this->extenders = array_merge($this->extenders, $extenders);
    }

    /**
     * @var string[]
     */
    protected $extensions = [];

    /**
     * Each argument should be an ID of an extension to be enabled.
     * Extensions other than the one currently being tested must be
     * listed in this extension's `composer.json` under `require` or
     * `require-dev`.
     * 
     * Note that this method will have no effect if called after the
     * application is booted.
     */
    protected function extension(string ...$extensions)
    {
        $this->extensions = array_merge($this->extensions, $extensions);
    }

    /**
     * @var array
     */
    protected $config = [];

    /**
     * Some Flarum code depends on config.php values. Flarum doesn't
     * offer a way to set them at runtime, so this method lets you
     * add/override them before boot.
     * 
     * You can use dot-separated syntax to assign values to subarrays.
     * 
     * For example:
     * 
     * `$this->config('a.b.c', 'value');` will result in the following:
     * 
     * [
     *     'a' => [
     *         'b' => ['c' => 'value']
     *     ]
     * ]
     * 
     * Note that this method will have no effect if called after the
     * application is booted.
     */
    protected function config(string $key, $value)
    {
        $this->config[$key] = $value;
    }

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * Some settings are used during application boot, so setting
     * them via `prepareDatabase` will be too late for the desired
     * effect. For instance, in core the active display name driver
     * is configured based on the `display_name_driver` setting.
     * That setting should be registered using this method.
     * 
     * Note that this method will have no effect if called after the
     * application is booted.
     */
    protected function setting(string $key, $value)
    {
        $this->settings[$key] = $value;
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
        $this->app();
        // Set in `BeginTransactionAndSetDatabase` extender.
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
