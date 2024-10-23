<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Api\Client;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Frontend
{
    /**
     * @var array<array{callback: callable, priority: int}>
     */
    protected array $content = [];

    public function __construct(
        protected Client $api,
        protected Container $container
    ) {
    }

    public function content(callable $callback, int $priority): void
    {
        $this->content[] = compact('callback', 'priority');
    }

    public function document(Request $request): Document
    {
        $forumApiDocument = $this->getForumDocument($request);

        $document = $this->container->makeWith(Document::class, compact('forumApiDocument', 'request'));

        $this->populate($document, $request);

        return $document;
    }

    protected function populate(Document $document, Request $request): void
    {
        $content = $this->content;

        usort($content, function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        foreach ($content as $item) {
            $item['callback']($document, $request);
        }
    }

    private function getForumDocument(Request $request): array
    {
        return $this->getResponseBody(
            $this->api->withoutErrorHandling()
                ->withParentRequest($request)
                ->get('/')
        );
    }

    private function getResponseBody(Response $response): array
    {
        return json_decode($response->getBody(), true);
    }
}
