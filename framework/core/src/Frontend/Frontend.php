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
     * @var callable[]
     */
    protected array $content = [];

    public function __construct(
        protected Client $api,
        protected Container $container
    ) {
    }

    public function content(callable $content): void
    {
        $this->content[] = $content;
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
        foreach ($this->content as $content) {
            $content($document, $request);
        }
    }

    private function getForumDocument(Request $request): array
    {
        return $this->getResponseBody(
            $this->api->withParentRequest($request)->get('/')
        );
    }

    private function getResponseBody(Response $response): array
    {
        return json_decode($response->getBody(), true);
    }
}
