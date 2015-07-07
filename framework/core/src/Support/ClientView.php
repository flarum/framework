<?php namespace Flarum\Support;

use Flarum\Api\Client;
use Flarum\Assets\AssetManager;
use Flarum\Core\Users\User;
use Psr\Http\Message\ServerRequestInterface as Request;
use Flarum\Locale\JsCompiler;

class ClientView
{
    protected $actor;

    protected $apiClient;

    protected $title;

    protected $document;

    protected $content;

    protected $request;

    protected $layout;

    public function __construct(
        Request $request,
        User $actor,
        Client $apiClient,
        $layout,
        AssetManager $assets,
        JsCompiler $locale
    ) {
        $this->request = $request;
        $this->actor = $actor;
        $this->apiClient = $apiClient;
        $this->layout = $layout;
        $this->assets = $assets;
        $this->locale = $locale;
    }

    public function setActor(User $actor)
    {
        $this->actor = $actor;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setDocument($document)
    {
        $this->document = $document;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function render()
    {
        $view = app('view')->file(__DIR__.'/../../views/app.blade.php');

        $forum = $this->getForumDocument();
        $data = $this->getDataFromDocument($forum);

        if ($this->actor->exists) {
            $user = $this->getUserDocument();
            $data = array_merge($data, $this->getDataFromDocument($user));
        }

        $view->data = $data;
        $view->session = $this->getSession();
        $view->title = ($this->title ? $this->title . ' - ' : '') . $forum->data->attributes->title;
        $view->document = $this->document;
        $view->forum = $forum->data;
        $view->layout = $this->layout;
        $view->content = $this->content;

        $view->styles = [$this->assets->getCssFile()];
        $view->scripts = [$this->assets->getJsFile(), $this->locale->getFile()];

        return $view->render();
    }

    public function __toString()
    {
        return $this->render();
    }

    protected function getForumDocument()
    {
        return $this->apiClient->send($this->actor, 'Flarum\Api\Actions\Forum\ShowAction')->getBody();
    }

    protected function getUserDocument()
    {
        // TODO: calling on the API here results in an extra query to get
        // the user + their groups, when we already have this information on
        // $this->actor. Can we simply run the CurrentUserSerializer
        // manually?
        $document = $this->apiClient->send(
            $this->actor,
            'Flarum\Api\Actions\Users\ShowAction',
            ['id' => $this->actor->id]
        )->getBody();

        return $document;
    }

    protected function getDataFromDocument($document)
    {
        $data[] = $document->data;

        if (isset($document->included)) {
            $data = array_merge($data, $document->included);
        }

        return $data;
    }

    protected function getSession()
    {
        return [
            'userId' => $this->actor->id,
            'token' => $this->request->getCookieParams()['flarum_remember'],
        ];
    }
}
