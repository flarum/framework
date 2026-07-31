<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\integration;

use Flarum\Database\AbstractModel;
use Flarum\Extend\ExtenderInterface;
use Flarum\Testing\integration\Setup\Bootstrapper;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

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
        $this->app()->getContainer()->make(Store::class)->flush();
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
            $this->config('env', 'testing');

            $bootstrapper = new Bootstrapper(
                $this->config,
                $this->extensions,
                $this->settings,
                $this->extenders
            );

            $this->app = $bootstrapper->run()->bootApp();

            $this->database = $bootstrapper->database;

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
        Arr::set($this->config, $key, $value);
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

    /** @var Connection|null */
    protected $database;

    /** @return Connection */
    protected function database(): ConnectionInterface
    {
        $this->app();

        // Set in `BeginTransactionAndSetDatabase` extender.
        return $this->database;
    }

    protected array $databaseContent = [];

    /**
     * @var array<string|class-string<Model>, array[]>
     */
    protected function prepareDatabase(array $tableData): void
    {
        $this->databaseContent = array_merge_recursive(
            $this->databaseContent,
            $tableData
        );
    }

    protected function populateDatabase(): void
    {
        /**
         * We temporarily disable foreign key checks to simplify this process.
         * SQLite ignores this statement since we are inside a transaction.
         * So we do that before starting a transaction.
         * @see BeginTransactionAndSetDatabase
         */
        $this->database()->getSchemaBuilder()->disableForeignKeyConstraints();

        if ($this->database()->getDriverName() === 'pgsql') {
            $this->database()->statement("SET session_replication_role = 'replica'");
        }

        $databaseContent = [];

        foreach ($this->databaseContent as $tableOrModelClass => $_rows) {
            if (class_exists($tableOrModelClass) && method_exists($tableOrModelClass, 'factory')) {
                /** @var AbstractModel $instance */
                $instance = (new $tableOrModelClass);

                $databaseContent[$instance->getTable()] = [
                    'rows' => $this->rowsThroughFactory($tableOrModelClass, $_rows),
                    'unique' => $instance->uniqueKeys ?? null,
                ];
            } else {
                if (class_exists($tableOrModelClass) && is_subclass_of($tableOrModelClass, Model::class)) {
                    $tableOrModelClass = (new $tableOrModelClass)->getTable();
                }

                $databaseContent[$tableOrModelClass] = [
                    'rows' => $_rows,
                    'unique' => null,
                ];
            }
        }

        $tables = [];

        // Then, insert all rows required for this test case.
        foreach ($databaseContent as $table => $data) {
            foreach ($data['rows'] as $row) {
                $unique = $row;

                if ($table === 'settings') {
                    $unique = Arr::only($row, ['key']);
                } elseif (isset($row['id'])) {
                    $unique = Arr::only($row, ['id']);
                } elseif ($data['unique']) {
                    $unique = Arr::only($row, $data['unique']);
                }

                $this->database()->table($table)->updateOrInsert($unique, $row);

                if (isset($row['id'])) {
                    $tables[$table] = 'id';
                }
            }
        }

        if ($this->database()->getDriverName() === 'pgsql') {
            // PgSQL doesn't auto-increment the sequence when inserting the IDs manually.
            foreach ($tables as $table => $id) {
                $wrappedTable = $this->database()->getSchemaGrammar()->wrapTable($table);
                $seq = $this->database()->getSchemaGrammar()->wrapTable($table.'_'.$id.'_seq');
                $this->database()->statement("SELECT setval('$seq', (SELECT MAX($id) FROM $wrappedTable))");
            }

            $this->database()->statement("SET session_replication_role = 'origin'");
        }

        // And finally, turn on foreign key checks again.
        $this->database()->getSchemaBuilder()->enableForeignKeyConstraints();
    }

    protected function throughFactory(string $modelClass, array $attributes): array
    {
        if (! method_exists($modelClass, 'factory')) {
            throw new RuntimeException("$modelClass must use the HasFactory trait and have a Factory class.");
        }

        /** @var \Illuminate\Database\Eloquent\Factories\Factory $factory */
        $factory = $modelClass::factory();

        return array_map(function (mixed $value) {
            return is_array($value) ? json_encode($value) : $value;
        }, $factory->raw($attributes));
    }

    protected function rowsThroughFactory(string $modelClass, array $rows): array
    {
        return array_map(function (array $row) use ($modelClass) {
            return $this->throughFactory($modelClass, $row);
        }, $rows);
    }

    /**
     * Send a full HTTP request through Flarum's middleware stack.
     */
    protected function send(ServerRequestInterface $request): ResponseInterface
    {
        if (! $this->detectsRepeatedQueries()) {
            return $this->server()->handle($request);
        }

        $database = $this->database();
        $wasLogging = $database->logging();

        $database->flushQueryLog();
        $database->enableQueryLog();

        try {
            $response = $this->server()->handle($request);
        } finally {
            $queries = $database->getQueryLog();

            if (! $wasLogging) {
                $database->disableQueryLog();
            }
        }

        $this->reportRepeatedQueries($request, $queries);

        return $response;
    }

    /**
     * Whether requests sent through this test case are inspected for N+1 query
     * patterns, failing the test when one is found.
     *
     * On by default: an N+1 is a defect, and finding it while writing the
     * feature is far cheaper than finding it in production. Override in a test
     * case that legitimately needs it off:
     *
     *     protected function detectsRepeatedQueries(): bool
     *     {
     *         return false; // and say why
     *     }
     *
     * Prefer {@see allowedRepeatedQueries()} to exempt one known query shape
     * rather than disabling the check for the whole test case. Setting
     * FLARUM_DETECT_REPEATED_QUERIES=0 turns it off for a whole run, which is
     * useful when bisecting an unrelated failure.
     */
    protected function detectsRepeatedQueries(): bool
    {
        $env = getenv('FLARUM_DETECT_REPEATED_QUERIES');

        return $env === false || $env === '' ? true : (bool) filter_var($env, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Repetitions of one query shape tolerated before a request is reported.
     * Small fixed-size loops (the actor, a couple of authors) are normal; an
     * N+1 grows with the fixture, so it clears any sensible threshold.
     */
    protected function repeatedQueryThreshold(): int
    {
        return (int) (getenv('FLARUM_REPEATED_QUERY_THRESHOLD') ?: 5);
    }

    /**
     * Query shapes this test case knowingly repeats, as substrings of the
     * normalised SQL — e.g. `'from `sessions`'`. A shape matching any of these
     * does not fail the test.
     *
     * Preferable to turning the check off wholesale: the rest of the request
     * stays covered. Say why each entry is legitimate.
     *
     * @return string[]
     */
    protected function allowedRepeatedQueries(): array
    {
        return [];
    }

    /**
     * @param array<array{query: string, bindings: array}> $queries
     */
    private function reportRepeatedQueries(ServerRequestInterface $request, array $queries): void
    {
        $repeats = RepeatedQueryDetector::findRepeats($queries, $this->repeatedQueryThreshold());

        foreach ($this->allowedRepeatedQueries() as $allowed) {
            $repeats = array_values(array_filter(
                $repeats,
                fn (array $repeat) => ! str_contains(RepeatedQueryDetector::normalise($repeat['sql']), $allowed)
            ));
        }

        if (! $repeats) {
            return;
        }

        $where = $request->getMethod().' '.$request->getUri()->getPath();

        // Work that grows with the data is a defect: one query per record
        // means a forum with a thousand records runs a thousand queries.
        $scaling = array_values(array_filter($repeats, RepeatedQueryDetector::scalesWithData(...)));

        // The rest repeat a handful of values: wasteful, but it does not
        // multiply as a forum grows. Worth knowing, not worth failing over.
        $wasteful = array_values(array_filter(
            $repeats,
            fn (array $repeat) => ! RepeatedQueryDetector::scalesWithData($repeat)
        ));

        if ($wasteful) {
            // Raised as a PHP warning: PHPUnit surfaces these against the test
            // that triggered them (with `displayDetailsOnTestsThatTriggerWarnings`
            // it prints the detail) without failing the run.
            @trigger_error(sprintf(
                "%s repeated queries for the same few values — consider memoising.\n%s",
                $where,
                RepeatedQueryDetector::describe($wasteful)
            ), E_USER_WARNING);
        }

        if ($scaling) {
            self::fail(sprintf(
                "%s ran one query per record — an N+1.\n\n%s\n\n"
                ."This is usually a relationship, permission check or serialized field resolved per model.\n"
                ."Load it for the whole page instead: eager loading, a batched relation, or one grouped\n"
                ."query. If the repetition is unavoidable, list the shape in\n"
                .'%s::allowedRepeatedQueries() with a comment saying why.',
                $where,
                RepeatedQueryDetector::describe($scaling),
                static::class
            ));
        }
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
