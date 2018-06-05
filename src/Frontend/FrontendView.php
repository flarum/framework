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
use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Frontend\Asset\CompilerInterface;
use Flarum\Frontend\Asset\LocaleJsCompiler;
use Flarum\Locale\LocaleManager;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Resource;

/**
 * This class represents a view which boots up Flarum's frontend app.
 */
class FrontendView implements Renderable
{
    /**
     * The title of the document, displayed in the <title> tag.
     *
     * @var null|string
     */
    public $title;

    /**
     * The language of the document, displayed as the value of the attribute `lang` in the <html> tag.
     *
     * @var null|string
     */
    public $language;

    /**
     * The text direction of the document, displayed as the value of the attribute `dir` in the <html> tag.
     *
     * @var null|string
     */
    public $direction;

    /**
     * The name of the frontend layout view to display.
     *
     * @var string
     */
    public $layout;

    /**
     * The SEO content of the page, displayed within the layout in <noscript> tags.
     *
     * @var string|Renderable
     */
    public $content;

    /**
     * A JSON-API document to be preloaded into the Flarum JS.
     *
     * @var null|array|object
     */
    public $document;

    /**
     * Other variables to preload into the Flarum JS.
     *
     * @var array
     */
    public $variables = [];

    /**
     * An array of meta tags to append to the page's <head>.
     *
     * @var array
     */
    public $meta = [];

    /**
     * The canonical URL for this page.
     *
     * This will signal to search engines what URL should be used for this
     * content, if it can be found under multiple addresses. This is an
     * important tool to tackle duplicate content.
     *
     * @var null|string
     */
    public $canonicalUrl;

    /**
     * An array of strings to append to the page's <head>.
     *
     * @var array
     */
    public $head = [];

    /**
     * An array of strings to prepend before the page's </body>.
     *
     * @var array
     */
    public $foot = [];

    /**
     * @var CompilerInterface
     */
    public $js;

    /**
     * @var CompilerInterface
     */
    public $css;

    /**
     * @var LocaleJsCompiler
     */
    public $localeJs;

    /**
     * @var CompilerInterface
     */
    public $localeCss;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $forum;

    /**
     * @var Client
     */
    protected $api;

    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @var AbstractSerializer
     */
    protected $userSerializer;

    /**
     * @param string $layout
     * @param Request $request
     * @param FrontendAssets $assets
     * @param Client $api
     * @param Factory $view
     * @param LocaleManager $locales
     * @param AbstractSerializer $userSerializer
     */
    public function __construct(string $layout, Request $request, FrontendAssets $assets, Client $api, Factory $view, LocaleManager $locales, AbstractSerializer $userSerializer)
    {
        $this->layout = $layout;
        $this->request = $request;
        $this->api = $api;
        $this->view = $view;
        $this->locales = $locales;
        $this->userSerializer = $userSerializer;

        $this->forum = $this->getForumDocument();

        $this->initializeAssets($assets);

        $this->addDefaultContent();
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $this->view->share('allowJs', ! array_get($this->request->getQueryParams(), 'nojs'));
        $this->view->share('forum', array_get($this->forum, 'data'));

        return $this->getView()->render();
    }

    /**
     * @return View
     */
    protected function getView(): View
    {
        return $this->view->make('flarum.forum::frontend.app')->with([
            'title' => $this->buildTitle(),
            'payload' => $this->buildPayload(),
            'layout' => $this->buildLayout(),
            'language' => $this->language,
            'direction' => $this->direction,
            'js' => $this->buildJs(),
            'head' => $this->buildHead(),
            'foot' => $this->buildFoot(),
        ]);
    }

    /**
     * @return string
     */
    protected function buildTitle(): string
    {
        return ($this->title ? $this->title.' - ' : '').array_get($this->forum, 'data.attributes.title');
    }

    /**
     * @return array
     */
    protected function buildPayload(): array
    {
        $data = $this->getDataFromDocument($this->forum);

        if ($this->request->getAttribute('actor')->exists) {
            $user = $this->getUserDocument();
            $data = array_merge($data, $this->getDataFromDocument($user));
        }

        $payload = [
            'resources' => $data,
            'session' => $this->buildSession(),
            'document' => $this->document,
            'locales' => $this->locales->getLocales(),
            'locale' => $this->locales->getLocale()
        ];

        return array_merge($payload, $this->variables);
    }

    /**
     * @return View
     */
    protected function buildLayout(): View
    {
        return $this->view->make($this->layout)
            ->with('content', $this->buildContent());
    }

    /**
     * @return View
     */
    protected function buildContent(): View
    {
        return $this->view->make('flarum.forum::frontend.content')
            ->with('content', $this->content);
    }

    /**
     * @return string
     */
    protected function buildHead(): string
    {
        $cssUrls = array_filter([$this->css->getUrl(), $this->localeCss->getUrl()]);

        $head = array_map(function ($url) {
            return '<link rel="stylesheet" href="'.e($url).'">';
        }, $cssUrls);

        if ($faviconUrl = array_get($this->forum, 'data.attributes.faviconUrl')) {
            $head[] = '<link rel="shortcut icon" href="'.e($faviconUrl).'">';
        }

        if ($this->canonicalUrl) {
            $head[] = '<link rel="canonical" href="'.e($this->canonicalUrl).'">';
        }

        $head = array_merge($head, array_map(function ($content, $name) {
            return '<meta name="'.e($name).'" content="'.e($content).'">';
        }, $this->meta, array_keys($this->meta)));

        return implode("\n", array_merge($head, $this->head));
    }

    /**
     * @return string
     */
    protected function buildJs(): string
    {
        $urls = array_filter([$this->js->getUrl(), $this->localeJs->getUrl()]);

        return implode("\n", array_map(function ($url) {
            return '<script src="'.e($url).'"></script>';
        }, $urls));
    }

    /**
     * @return string
     */
    protected function buildFoot(): string
    {
        return implode("\n", $this->foot);
    }

    /**
     * Get the result of an API request to show the forum.
     *
     * @return array
     */
    protected function getForumDocument(): array
    {
        $actor = $this->request->getAttribute('actor');

        $response = $this->api->send(ShowForumController::class, $actor);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get the result of an API request to show the current user.
     *
     * @return array
     */
    protected function getUserDocument(): array
    {
        $actor = $this->request->getAttribute('actor');

        $this->userSerializer->setActor($actor);

        $resource = new Resource($actor, $this->userSerializer);

        $document = new Document($resource->with('groups'));

        return $document->toArray();
    }

    /**
     * Get information about the current session.
     *
     * @return array
     */
    protected function buildSession(): array
    {
        $actor = $this->request->getAttribute('actor');
        $session = $this->request->getAttribute('session');

        return [
            'userId' => $actor->id,
            'csrfToken' => $session->token()
        ];
    }

    private function initializeAssets(FrontendAssets $assets)
    {
        $this->js = $assets->getJs();
        $this->css = $assets->getCss();

        $locale = $this->locales->getLocale();

        $this->localeJs = $assets->getLocaleJs($locale);
        $this->localeCss = $assets->getLocaleCss($locale);

        foreach ($this->locales->getJsFiles($locale) as $file) {
            $this->localeJs->addFile($file);
        }

        foreach ($this->locales->getCssFiles($locale) as $file) {
            $this->localeCss->addFile($file);
        }
    }

    private function addDefaultContent()
    {
        $this->langauge = $this->locales->getLocale();
        $this->direction = 'ltr';

        $this->meta['viewport'] = 'width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1';
        $this->meta['description'] = array_get($this->forum, 'data.attributes.description');
        $this->meta['theme-color'] = array_get($this->forum, 'data.attributes.themePrimaryColor');

        if (array_get($this->request->getQueryParams(), 'nojs')) {
            $this->meta['robots'] = 'noindex';
        }

        $this->head['font'] = '<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,400,700,600">';
    }

    /**
     * Get an array of data by merging the 'data' and 'included' keys of a JSON-API document.
     *
     * @param array $document
     * @return array
     */
    private function getDataFromDocument(array $document): array
    {
        $data[] = $document['data'];

        if (isset($document['included'])) {
            $data = array_merge($data, $document['included']);
        }

        return $data;
    }
}
