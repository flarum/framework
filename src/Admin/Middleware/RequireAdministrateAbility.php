<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Admin\Middleware;

use Exception;
use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Forum\Controller\LogInController;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Stratigility\MiddlewareInterface;

class RequireAdministrateAbility implements MiddlewareInterface
{
    use AssertPermissionTrait;

    /**
     * @var LogInController
     */
    private $logInController;

    /**
     * @var Factory
     */
    private $view;

    /**
     * @param LogInController $logInController
     * @param Factory $view
     */
    public function __construct(LogInController $logInController, Factory $view)
    {
        $this->logInController = $logInController;
        $this->view = $view;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        try {
            $this->assertAdminAndSudo($request);
        } catch (Exception $e) {
            if ($request->getMethod() === 'POST') {
                $response = $this->logInController->handle($request);

                if ($response->getStatusCode() === 200) {
                    return $response
                        ->withStatus(302)
                        ->withHeader('location', app('Flarum\Admin\UrlGenerator')->toRoute('index'));
                }
            }

            return new HtmlResponse(
                $this->view->make('flarum.admin::login')
                    ->with('token', $request->getAttribute('session')->csrf_token)
                    ->render()
            );
        }

        return $out ? $out($request, $response) : $response;
    }
}
