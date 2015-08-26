<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Actions;

use Flarum\Api\Request;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core\Exceptions\ValidationFailureException;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Zend\Diactoros\Response\JsonResponse;

abstract class JsonApiAction implements Action
{
    /**
     * Handle an API request and return an API response, handling any relevant
     * (API-related) exceptions that are thrown.
     *
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(Request $request)
    {
        // TODO: This is gross. Move this error handling code to middleware?
        try {
            return $this->respond($request);
        } catch (ValidationException $e) {
            $errors = [];
            foreach ($e->errors()->toArray() as $field => $messages) {
                $errors[] = [
                    'detail' => implode("\n", $messages),
                    'path' => $field
                ];
            }
            return new JsonResponse(['errors' => $errors], 422);
        } catch (\Flarum\Core\Exceptions\ValidationException $e) {
            $errors = [];
            foreach ($e->getMessages() as $path => $detail) {
                $errors[] = compact('path', 'detail');
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
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    abstract protected function respond(Request $request);
}
