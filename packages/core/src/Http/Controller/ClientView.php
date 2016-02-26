<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Controller;

use Flarum\Api\Client;
use Flarum\Asset\AssetManager;
use Flarum\Core\User;
use Flarum\Locale\JsCompiler;
use Illuminate\Contracts\Support\Renderable;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * This class represents a view which boots up Flarum's client.
 */
class ClientView implements Renderable
{
    /**
     * The user who is using the client.
     *
     * @var User
     */
    protected $actor;

    /**
     * The title of the document, displayed in the <title> tag.
     *
     * @var null|string
     */
    protected $title;

    /**
     * The SEO content of the page, displayed in <noscript> tags.
     *
     * @var string
     */
    protected $content;

    /**
     * The path to the client layout view to display.
     *
     * @var string
     */
    protected $layout;

    /**
     * An API response that should be preloaded into the page.
     *
     * @var null|array|object
     */
    protected $document;

    /**
     * Other variables to preload into the page.
     *
     * @var array
     */
    protected $variables = [];

    /**
     * An array of JS modules to import before booting the app.
     *
     * @var array
     */
    protected $bootstrappers = ['locale'];

    /**
     * An array of strings to append to the page's <head>.
     *
     * @var array
     */
    protected $headStrings = [];

    /**
     * An array of strings to prepend before the page's </body>.
     *
     * @var array
     */
    protected $footStrings = [];

    /**
     * @var Client
     */
    protected $api;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var AssetManager
     */
    protected $assets;

    /**
     * @var JsCompiler
     */
    protected $localeJs;

    /**
     * @param Client $api
     * @param Request $request
     * @param User $actor
     * @param AssetManager $assets
     * @param string $layout
     * @param JsCompiler $localeJs
     */
    public function __construct(
        Client $api,
        Request $request,
        User $actor,
        AssetManager $assets,
        $layout,
        JsCompiler $localeJs = null
    ) {
        $this->api = $api;
        $this->request = $request;
        $this->actor = $actor;
        $this->assets = $assets;
        $this->layout = $layout;
        $this->localeJs = $localeJs;

        $this->addHeadString('<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,400,700,600">', 'font');
    }

    /**
     * The title of the document, to be displayed in the <title> tag.
     *
     * @param null|string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Set the SEO content of the page, to be displayed in <noscript> tags.
     *
     * @param null|string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Set the name of the client layout view to display.
     *
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    /**
     * Add a string to be appended to the page's <head>.
     *
     * @param string $string
     */
    public function addHeadString($string, $name = null)
    {
        if ($name) {
            $this->headStrings[$name] = $string;
        } else {
            $this->headStrings[] = $string;
        }
    }

    /**
     * Add a string to be prepended before the page's </body>.
     *
     * @param string $string
     */
    public function addFootString($string)
    {
        $this->footStrings[] = $string;
    }

    /**
     * Set an API response to be preloaded into the page. This should be a
     * JSON-API document.
     *
     * @param null|array|object $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }

    /**
     * Set a variable to be preloaded into the app.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setVariable($name, $value)
    {
        $this->variables[$name] = $value;
    }

    /**
     * Add a JavaScript module to be imported before the app is booted.
     *
     * @param string $string
     */
    public function addBootstrapper($string)
    {
        $this->bootstrappers[] = $string;
    }

    /**
     * Get the view's asset manager.
     *
     * @return AssetManager
     */
    public function getAssets()
    {
        return $this->assets;
    }

    /**
     * Get the string contents of the view.
     *
     * @return string
     */
    public function render()
    {
        $view = app('view')->file(__DIR__.'/../../../views/app.blade.php');

        $forum = $this->getForumDocument();
        $data = $this->getDataFromDocument($forum);

        if ($this->actor->exists) {
            $user = $this->getUserDocument();
            $data = array_merge($data, $this->getDataFromDocument($user));
        }

        $view->app = [
            'preload' => [
                'data' => $data,
                'session' => $this->getSession(),
                'document' => $this->document
            ]
        ] + $this->variables;
        $view->bootstrappers = $this->bootstrappers;

        $noJs = array_get($this->request->getQueryParams(), 'nojs');

        $view->title = ($this->title ? $this->title.' - ' : '').$forum->data->attributes->title;
        $view->forum = $forum->data;
        $view->layout = app('view')->file($this->layout, [
            'forum' => $forum->data,
            'content' => app('view')->file(__DIR__.'/../../../views/content.blade.php', [
                'content' => $this->content,
                'noJs' => $noJs,
                'forum' => $forum->data
            ])
        ]);
        $view->noJs = $noJs;

        $view->styles = [$this->assets->getCssFile()];
        $view->scripts = [$this->assets->getJsFile()];

        if ($this->localeJs) {
            $view->scripts[] = $this->localeJs->getFile();
        }

        $view->head = implode("\n", $this->headStrings);
        $view->foot = implode("\n", $this->footStrings);

        return $view->render();
    }

    /**
     * Get the string contents of the view.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Get the result of an API request to show the forum.
     *
     * @return object
     */
    protected function getForumDocument()
    {
        return json_decode($this->api->send('Flarum\Api\Controller\ShowForumController', $this->actor)->getBody());
    }

    /**
     * Get the result of an API request to show the current user.
     *
     * @return object
     */
    protected function getUserDocument()
    {
        // TODO: calling on the API here results in an extra query to get
        // the user + their groups, when we already have this information on
        // $this->actor. Can we simply run the CurrentUserSerializer
        // manually? Or can we somehow inject this data into the ShowDiscussionController?
        $document = json_decode($this->api->send(
            'Flarum\Api\Controller\ShowUserController',
            $this->actor,
            ['id' => $this->actor->id]
        )->getBody());

        return $document;
    }

    /**
     * Get an array of data by merging the 'data' and 'included' keys of a
     * JSON-API document.
     *
     * @param object $document
     * @return array
     */
    protected function getDataFromDocument($document)
    {
        $data[] = $document->data;

        if (isset($document->included)) {
            $data = array_merge($data, $document->included);
        }

        return $data;
    }

    /**
     * Get information about the current session.
     *
     * @return array
     */
    protected function getSession()
    {
        $session = $this->request->getAttribute('session');

        return [
            'userId' => $this->actor->id,
            'csrfToken' => $session->get('csrf_token')
        ];
    }

    /**
     * @return User
     */
    public function getActor()
    {
        return $this->actor;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
