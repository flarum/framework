<?php namespace Flarum\Api\Actions;

use Illuminate\Http\Request;
use Illuminate\Contracts\Bus\Dispatcher;
use Tobscure\JsonApi\Document;
use Flarum\Support\Actor;
use Flarum\Api\Events\CommandWillBeDispatched;
use Flarum\Api\Events\WillRespondWithDocument;
use Flarum\Support\Action;
use Config;
use App;
use Response;

abstract class BaseAction extends Action
{
    public function __construct(Actor $actor, Dispatcher $bus)
    {
        $this->actor = $actor;
        $this->bus = $bus;
    }

    public function handle(Request $request, $routeParams = [])
    {
        if (str_contains($request->header('CONTENT_TYPE'), 'application/vnd.api+json')) {
            $input = $request->json();
        } else {
            $input = $request;
        }
        $params = array_merge($input->all(), $routeParams);

        return $this->call($params);
    }

    public function call($params = [])
    {
        $params = new ApiParams($params);

        return $this->run($params);
    }

    /**
     * @param ApiParams $params
     * @return mixed
     */
    protected function run(ApiParams $params)
    {
        // Should be implemented by subclasses
    }

    public function hydrate($object, $params)
    {
        foreach ($params as $k => $v) {
            $object->$k = $v;
        }
    }

    protected function dispatch($command, $params = [])
    {
        $this->event(new CommandWillBeDispatched($command, $params));
        return $this->bus->dispatch($command);
    }

    protected function event($event)
    {
        event($event);
    }

    public function document()
    {
        return new Document;
    }

    protected function buildUrl($route, $params = [], $input = [])
    {
        $url = route('flarum.api.'.$route, $params);
        $queryString = $input ? '?'.http_build_query($input) : '';

        return $url.$queryString;
    }

    protected function respondWithoutContent($statusCode = 204, $headers = [])
    {
        return Response::make('', $statusCode, $headers);
    }

    protected function respondWithArray($array, $statusCode = 200, $headers = [])
    {
        return Response::json($array, $statusCode, $headers);
    }

    protected function respondWithDocument($document, $statusCode = 200, $headers = [])
    {
        $headers['Content-Type'] = 'application/vnd.api+json';

        $this->event(new WillRespondWithDocument($document, $statusCode, $headers));

        return $this->respondWithArray($document->toArray(), $statusCode, $headers);
    }

    protected function respondWithErrors($errors, $httpCode = 500)
    {
        return Response::json(['errors' => $errors], $httpCode);
    }

    protected function respondWithError($error, $httpCode = 500, $detail = null)
    {
        $error = ['code' => $error];

        if ($detail) {
            $error['detail'] = $detail;
        }

        return $this->respondWithErrors([$error], $httpCode);
    }
}
