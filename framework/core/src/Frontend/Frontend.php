<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Api\Client;
use Flarum\Frontend\Driver\TitleDriverInterface;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Frontend
{
    /**
     * @var callable[]
     */
    protected array $content = [];

    public function __construct(
        protected Factory $view,
        protected Client $api,
        protected TitleDriverInterface $titleDriver
    ) {
    }

    public function content(callable $content): void
    {
        $this->content[] = $content;
    }

    public function document(Request $request): Document
    {
        $forumDocument = $this->getForumDocument($request);

        $document = new Document($this->view, $forumDocument, $request, $this->titleDriver);

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
