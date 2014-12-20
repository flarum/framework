<?php namespace Flarum\Api\Actions;

use Illuminate\Routing\Controller;
use Tobscure\JsonApi\Document;
use Laracasts\Commander\CommandBus;
use Response;
use Event;
use App;
use Config;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core\Support\Exceptions\ValidationFailureException;

abstract class Base extends Controller
{
    protected $request;

    protected $document;

    protected $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }
    
    abstract protected function run();

    public function handle($request, $parameters)
    {
        $this->registerErrorHandlers();

        $this->request = $request;
        $this->parameters = $parameters;

        $this->document = new Document;
        $this->document->addMeta('profile', '?');

        return $this->run();
    }

    public function param($key, $default = null)
    {
        return array_get($this->parameters, $key, $default);
    }

    public function input($key, $default = null)
    {
        return $this->request->input($key, $default);
    }

    public function fillCommandWithInput($command, $inputKey = null)
    {
        $input = $inputKey ? $this->input($inputKey) : $this->request->input->all();

        foreach ($input as $k => $v) {
            $command->$k = $v;
        }
    }

    protected function inputRange($key, $default = null, $min = null, $max = null)
    {
        $value = (int) $this->input($key, $default);

        if (! is_null($min)) {
            $value = max($value, $min);
        }
        if (! is_null($max)) {
            $value = min($value, $max);
        }
        return $value;
    }

    protected function included($available)
    {
        $requested = explode(',', $this->input('include'));
        return array_intersect($available, $requested);
    }

    protected function explodeIds($ids)
    {
        return array_unique(array_map('intval', array_filter(explode(',', $ids))));
    }

    protected function inputIn($key, $options)
    {
        $value = $this->input($key);

        if (array_key_exists($key, $options)) {
            return $options[$key];
        }
        if (! in_array($value, $options)) {
            $value = reset($options);
        }

        return $value;
    }

    protected function sort($options)
    {
        $criteria = (string) $this->input('sort', '');
        $order = $criteria ? 'asc' : null;

        if ($criteria && $criteria[0] == '-') {
            $order = 'desc';
            $criteria = substr($criteria, 1);
        }

        if (! in_array($criteria, $options)) {
            $criteria = reset($options);
        }

        return [
            'by' => $criteria,
            'order' => $order,
            'string' => ($order == 'desc' ? '-' : '').$criteria
        ];
    }

    protected function start()
    {
        return $this->inputRange('start', 0, 0);
    }

    protected function count($default, $max = 100)
    {
        return $this->inputRange('count', $default, 1, $max);
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
        // @todo remove this
        $headers['Access-Control-Allow-Origin'] = 'http://0.0.0.0:4200';

        return Response::json($array, $statusCode, $headers);
    }

    protected function respondWithDocument($statusCode = 200, $headers = [])
    {
        // @todo remove this
        $this->document->addMeta('pageload', microtime(true) - LARAVEL_START);

        Event::fire('flarum.api.willRespondWithDocument', [$this->document]);

        $headers['Content-Type'] = 'application/vnd.api+json';

        return $this->respondWithArray($this->document->toArray(), $statusCode, $headers);
    }

    // @todo fix this
    protected function call($name, $params, $method, $input)
    {
        Input::replace($input);

        $url = URL::action('\\Flarum\\Api\\Controllers\\'.$name, $params, false);
        $request = Request::create($url, $method);
        $json = Route::dispatch($request)->getContent();

        return json_decode($json, true);
    }

    protected function registerErrorHandlers()
    {
        if (! Config::get('app.debug')) {
            App::error(function ($exception, $code) {
                return $this->respondWithError('ApplicationError', $code);
            });
        }

        App::error(function (ModelNotFoundException $exception) {
            return $this->respondWithError('ResourceNotFound', 404);
        });

        App::error(function (ValidationFailureException $exception) {
            $errors = [];
            foreach ($exception->getErrors()->getMessages() as $field => $messages) {
                $errors[] = [
                    'code' => 'ValidationFailure',
                    'detail' => implode("\n", $messages),
                    'path' => $field
                ];
            }
            return $this->respondWithErrors($errors, 422);
        });
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
