<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Testing;

use Flarum\Extend\Csrf;
use LogicException;
use Psr\Http\Message\ResponseInterface;

/** @phpstan-require-extends \Flarum\Testing\integration\TestCase */
trait BuildsAuditRequests
{
    protected function exemptAuditAuthRoutesFromCsrf(): void
    {
        if ($this->app !== null) {
            throw new LogicException('Configure Audit auth-route exemptions before the application boots.');
        }

        $this->extend(
            (new Csrf())
                ->exemptRoute('register')
                ->exemptRoute('login')
                ->exemptRoute('logout')
                ->exemptRoute('confirmEmail.submit')
                ->exemptRoute('savePassword')
                ->exemptRoute('forgot')
        );
    }

    /**
     * Send an authenticated request and assert the response status. Echoes the body on a 422
     * so validation failures (which are otherwise logged nowhere) are easy to debug.
     */
    protected function sendSuccessfulRequest(string $method, string $path, array $options = [], int $statusCode = 200, ?int $authenticatedAs = 1): ResponseInterface
    {
        $response = $this->send($this->request($method, $path, $options + [
            'authenticatedAs' => $authenticatedAs,
        ]));

        if ($response->getStatusCode() === 422) {
            echo $response->getBody()->getContents();

            $response->getBody()->rewind();
        }

        $this->assertEquals($statusCode, $response->getStatusCode(), 'Assert request status code');

        return $response;
    }

    /**
     * Send a guest request to a route exempted by exemptAuditAuthRoutesFromCsrf(),
     * avoiding the unreliable GET-token-then-POST dance.
     */
    protected function sendForumCsrfRequest(string $method, string $path, array $options = [], int $statusCode = 200): ResponseInterface
    {
        $response = $this->send($this->request($method, $path, $options));

        $this->assertEquals($statusCode, $response->getStatusCode(), 'Assert request status code');

        return $response;
    }
}
