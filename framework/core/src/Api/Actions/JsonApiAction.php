<?php namespace Flarum\Api\Actions;

use Flarum\Api\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core\Exceptions\ValidationFailureException;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Zend\Diactoros\Response\JsonResponse;

abstract class JsonApiAction implements ActionInterface
{
    /**
     * Handle an API request and return an API response, handling any relevant
     * (API-related) exceptions that are thrown.
     *
     * @param \Flarum\Api\Request $request
     * @return \Psr\Http\Message\ResponseInterface
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
            return new JsonResponse(null, 401);
        } catch (ModelNotFoundException $e) {
            return new JsonResponse(null, 404);
        }
    }

    /**
     * Handle an API request and return an API response.
     *
     * @param \Flarum\Api\Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    abstract protected function respond(Request $request);
}
