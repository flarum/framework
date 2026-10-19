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

/**
 * GitHub's REST API, and only it: the host is fixed and the path is built
 * from a parsed Reference, so no input can point the request anywhere else.
 * No redirects, short timeouts, and a failure is reported rather than
 * retried, so a slow GitHub never holds a forum request for long.
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
            $response = (new Guzzle(['base_uri' => 'https://api.github.com/']))->get($path, [
                'headers' => $headers,
                'http_errors' => false,
                'allow_redirects' => false,
                'connect_timeout' => 3,
                'timeout' => 6,
            ]);
        } catch (GuzzleException $e) {
            throw new Unavailable($e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();

        if ($status === 404 || $status === 410) {
            return null;
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
}
