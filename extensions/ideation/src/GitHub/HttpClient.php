<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\GitHub;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * GitHub's REST API, and only it: the host is fixed and the path is built
 * from a parsed Reference, so no input can point the request anywhere else.
 * No redirects, short timeouts, and a failure is reported rather than
 * retried, so a slow GitHub never holds a forum request for long.
 *
 * A failure is told apart by what it means for the next request: GitHub
 * down or the rate limit spent is Unavailable (stop asking for now), one
 * item moved or blocked is Gone (skip it, ask about the others).
 */
class HttpClient implements Client
{
    public function __construct(
        protected Config $config
    ) {
    }

    public function fetch(Reference $ref): ?array
    {
        $path = $ref->type === 'milestone'
            ? "repos/{$ref->repo}/milestones/{$ref->number}"
            : "repos/{$ref->repo}/issues/{$ref->number}";

        $headers = [
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
            'User-Agent' => 'flarum-ideation',
        ];

        if ($token = $this->config->token()) {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        try {
            $response = $this->get($path, $headers);
        } catch (GuzzleException $e) {
            throw new Unavailable($e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();

        if ($status === 404 || $status === 410) {
            return null;
        }

        // Moved (a renamed or transferred repository), or blocked for legal
        // reasons. The redirect is not followed: the host and the
        // repository allowlist are only ever checked for the address asked.
        if (in_array($status, [301, 302, 307, 308, 451], true)) {
            throw new Gone("GitHub answered $status");
        }

        // A 403 is a rate limit (the primary one, or a secondary one that
        // says so in a retry-after or its message), and otherwise this item
        // refused: a repository blocked or its access restricted.
        if ($status === 403
            && ! in_array($response->getHeaderLine('x-ratelimit-remaining'), ['', '0'], true)
            && ! $response->hasHeader('retry-after')
            && ! str_contains(strtolower((string) $response->getBody()), 'rate limit')) {
            throw new Gone('GitHub answered 403');
        }

        if ($status !== 200) {
            throw new Unavailable("GitHub answered $status");
        }

        $data = json_decode((string) $response->getBody(), true);

        if (! is_array($data)) {
            throw new Unavailable('GitHub sent something that is not JSON');
        }

        return $ref->type === 'milestone' ? Item::milestone($data) : Item::issue($data);
    }

    /** @throws GuzzleException */
    protected function get(string $path, array $headers): ResponseInterface
    {
        return (new Guzzle(['base_uri' => 'https://api.github.com/']))->get($path, [
            'headers' => $headers,
            'http_errors' => false,
            'allow_redirects' => false,
            'connect_timeout' => 3,
            'timeout' => 6,
        ]);
    }
}
