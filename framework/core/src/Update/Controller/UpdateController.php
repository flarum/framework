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
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;

class UpdateController implements RequestHandlerInterface
{
    public function __construct(
        protected MigrateCommand $command,
        protected Config $config
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $input = $request->getParsedBody();

        if (! $this->verifyCredentials(is_array($input) ? $input : [])) {
            return new HtmlResponse('Incorrect database credentials.', 500);
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

    /**
     * Confirm the caller is the admin, by the only means available before the
     * app can authenticate anyone: echoing back the database credentials that
     * config.php holds.
     *
     * For MySQL, MariaDB and PostgreSQL the username and password must both
     * match. On a passwordless database the password half is an empty string
     * on both sides, so the username is what a passing visitor would not know —
     * and checking it unconditionally is what closed the old hole, where a null
     * password meant no check ran at all.
     *
     * SQLite has neither a username nor a password — it is a file, not a
     * network service. There the database file's name stands in as the secret:
     * still something an admin knows and an anonymous visitor does not.
     *
     * @param array<string, mixed> $input
     */
    private function verifyCredentials(array $input): bool
    {
        $username = (string) $this->config['database.username'];
        $password = (string) $this->config['database.password'];

        if ($username === '' && $password === '') {
            // No credentials to check — this is SQLite. Fall back to the
            // database name, the one thing in its config an outsider would not
            // know. It is a weak secret (defaults like `flarum.sqlite` are
            // guessable), but a weak check is worth more than none, and it
            // closes the bodyless-POST hole for this driver too.
            $database = (string) $this->config['database.database'];

            return hash_equals($database, (string) Arr::get($input, 'databaseName', ''));
        }

        // Both comparisons are evaluated before they are combined, so a failed
        // username check does not short-circuit the password check and reveal,
        // through timing, which half was wrong.
        $usernameOk = hash_equals($username, (string) Arr::get($input, 'databaseUsername', ''));
        $passwordOk = hash_equals($password, (string) Arr::get($input, 'databasePassword', ''));

        return $usernameOk && $passwordOk;
    }
}
