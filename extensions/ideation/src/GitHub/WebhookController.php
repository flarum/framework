<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\GitHub;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * POST /api/ideation/github/webhook — GitHub tells us an issue, pull request
 * or milestone changed.
 *
 * 🚨 Nothing is read until the signature checks out: an HMAC-SHA256 of the
 * raw body with the forum's secret, compared in constant time. No secret set
 * (or the integration off) means every delivery is refused; this endpoint
 * never runs unauthenticated. It only ever updates links that already exist,
 * so even a valid delivery cannot add anything to the forum.
 */
class WebhookController implements RequestHandlerInterface
{
    /** GitHub caps payloads at 25 MB; the events used here are a few KB. */
    private const MAX_BYTES = 2_000_000;

    public function __construct(
        protected Config $config,
        protected Links $links
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $secret = $this->config->secret();

        if (! $this->config->enabled() || $secret === '') {
            return new EmptyResponse(404);
        }

        $body = $request->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        $raw = $body->getContents();

        if (strlen($raw) > self::MAX_BYTES) {
            return new EmptyResponse(413);
        }

        $signature = $request->getHeaderLine('X-Hub-Signature-256');

        if (! str_starts_with($signature, 'sha256=') || ! hash_equals('sha256='.hash_hmac('sha256', $raw, $secret), $signature)) {
            return new EmptyResponse(401);
        }

        $payload = json_decode($raw, true);
        $repo = is_array($payload) ? (string) ($payload['repository']['full_name'] ?? '') : '';

        if ($repo === '') {
            return new JsonResponse(['ok' => true, 'updated' => 0]);
        }

        $updated = 0;

        switch ($request->getHeaderLine('X-GitHub-Event')) {
            case 'issues':
                $updated += $this->item($repo, $payload['issue'] ?? null, Item::issue(...));
                $updated += $this->item($repo, $payload['issue']['milestone'] ?? null, Item::milestone(...));
                break;
            case 'pull_request':
                $updated += $this->item($repo, $payload['pull_request'] ?? null, Item::issue(...));
                $updated += $this->item($repo, $payload['pull_request']['milestone'] ?? null, Item::milestone(...));
                break;
            case 'milestone':
                $updated += $this->item($repo, $payload['milestone'] ?? null, Item::milestone(...));
                break;
        }

        return new JsonResponse(['ok' => true, 'updated' => $updated]);
    }

    private function item(string $repo, mixed $data, callable $normalise): int
    {
        if (! is_array($data) || ! isset($data['number'])) {
            return 0;
        }

        return $this->links->apply($repo, (int) $data['number'], $normalise($data));
    }
}
