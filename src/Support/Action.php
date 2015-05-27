<?php namespace Flarum\Support;

use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response;

abstract class Action
{
    abstract public function handle(Request $request, $routeParams = []);

    public function __construct(Actor $actor, Dispatcher $bus)
    {
        $this->actor = $actor;
        $this->bus = $bus;
    }

    protected function callAction($class, $params = [])
    {
        $action = app($class);
        return $action->call($params);
    }

    /**
     * @param string $url
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function redirectTo($url)
    {
        $content = sprintf('<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="refresh" content="1;url=%1$s" />

        <title>Redirecting to %1$s</title>
    </head>
    <body>
        Redirecting to <a href="%1$s">%1$s</a>.
    </body>
</html>', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));

        return new Response($content, 302, ['location' => $url]);
    }
}
