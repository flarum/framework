<?php namespace Flarum\Api\Actions;

use Flarum\Api\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core\Exceptions\ValidationFailureException;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Zend\Diactoros\Response;

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
            return $this->json(['errors' => $errors], 422);
        } catch (PermissionDeniedException $e) {
            return $this->json(null, 401);
        } catch (ModelNotFoundException $e) {
            return $this->json(null, 404);
        }
    }

    protected function json($data = null, $status = 200)
    {
        if ($data === null) {
            $data = new \ArrayObject();
        }

        $data = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        $response = new Response('php://memory', $status);
        $response->getBody()->write($data);

        return $response;
    }

    /**
     * Handle an API request and return an API response.
     *
     * @param \Flarum\Api\Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    abstract protected function respond(Request $request);
}
