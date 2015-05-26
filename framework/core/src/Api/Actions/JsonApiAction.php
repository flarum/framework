<?php namespace Flarum\Api\Actions;

use Closure;
use Flarum\Api\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core\Exceptions\ValidationFailureException;
use Flarum\Core\Exceptions\PermissionDeniedException;

abstract class JsonApiAction implements ActionInterface
{
    /**
     * Handle an API request and return an API response, handling any relevant
     * (API-related) exceptions that are thrown.
     *
     * @param \Flarum\Api\Request $request
     * @return \Flarum\Api\Response
     */
    public function handle(Request $request)
    {
        try {
            return $this->respond($request);
        } catch (ValidationFailureException $e) {
            $errors = [];
            foreach ($e->getErrors()->getMessages() as $field => $messages) {
                $errors[] = [
                    'detail' => implode("\n", $messages),
                    'path' => $field
                ];
            }
            return new JsonResponse(['errors' => $errors], 422);
        } catch (PermissionDeniedException $e) {
            return new Response(null, 401);
        } catch (ModelNotFoundException $e) {
            return new Response(null, 404);
        }
    }

    /**
     * Handle an API request and return an API response.
     *
     * @param \Flarum\Api\Request $request
     * @return \Flarum\Api\Response
     */
    abstract protected function respond(Request $request);
}
