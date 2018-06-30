<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Api\Client;
use Flarum\Api\Controller\ShowForumController;
use Flarum\Frontend\Compiler\CompilerInterface;
use Flarum\Frontend\Content\ContentInterface;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HtmlDocumentFactory
{
    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var Client
     */
    protected $api;

    /**
     * @var CompilerFactory
     */
    protected $assets;

    /**
     * @var bool
     */
    protected $commitAssets;

    /**
     * @var ContentInterface[]
     */
    protected $content = [];

    /**
     * @param Factory $view
     * @param Client $api
     * @param CompilerFactory|null $assets
     * @param bool $commitAssets
     */
    public function __construct(Factory $view, Client $api, CompilerFactory $assets = null, bool $commitAssets = false)
    {
        $this->view = $view;
        $this->api = $api;
        $this->assets = $assets;
        $this->commitAssets = $commitAssets;
    }

    /**
     * @param ContentInterface $content
     */
    public function add($content)
    {
        $this->content[] = $content;
    }

    /**
     * @param Request $request
     * @return HtmlDocument
     */
    public function make(Request $request): HtmlDocument
    {
        $forumDocument = $this->getForumDocument($request);

        $view = new HtmlDocument($this->view, $forumDocument);

        $locale = $request->getAttribute('locale');

        $js = [$this->assets->makeJs(), $this->assets->makeLocaleJs($locale)];
        $css = [$this->assets->makeCss(), $this->assets->makeLocaleCss($locale)];

        $this->maybeCommitAssets(array_merge($js, $css));

        $view->js = array_merge($view->js, $this->getUrls($js));
        $view->css = array_merge($view->css, $this->getUrls($css));

        $this->populate($view, $request);

        return $view;
    }

    /**
     * @return CompilerFactory
     */
    public function getAssets(): CompilerFactory
    {
        return $this->assets;
    }

    /**
     * @param CompilerFactory $assets
     */
    public function setAssets(CompilerFactory $assets)
    {
        $this->assets = $assets;
    }

    /**
     * @param HtmlDocument $view
     * @param Request $request
     */
    protected function populate(HtmlDocument $view, Request $request)
    {
        foreach ($this->content as $content) {
            $content->populate($view, $request);
        }
    }

    /**
     * @param Request $request
     * @return array
     */
    private function getForumDocument(Request $request): array
    {
        $actor = $request->getAttribute('actor');

        return $this->getResponseBody(
            $this->api->send(ShowForumController::class, $actor)
        );
    }

    /**
     * @param Response $response
     * @return array
     */
    private function getResponseBody(Response $response)
    {
        return json_decode($response->getBody(), true);
    }

    private function maybeCommitAssets(array $compilers)
    {
        if ($this->commitAssets) {
            foreach ($compilers as $compiler) {
                $compiler->commit();
            }
        }
    }

    /**
     * @param CompilerInterface[] $compilers
     * @return string[]
     */
    private function getUrls(array $compilers)
    {
        return array_filter(array_map(function (CompilerInterface $compiler) {
            return $compiler->getUrl();
        }, $compilers));
    }

    /**
     * @return bool
     */
    public function getCommitAssets(): bool
    {
        return $this->commitAssets;
    }

    /**
     * @param bool $commitAssets
     */
    public function setCommitAssets(bool $commitAssets)
    {
        $this->commitAssets = $commitAssets;
    }
}
