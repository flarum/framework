<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\Auth;

use Carbon\Carbon;
use Flarum\Api\ApiKey;
use Flarum\Api\Client;
use Flarum\Api\Controller\CreateGroupController;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Flarum\User\Guest;
use Flarum\User\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Stratigility\MiddlewarePipe;

class AuthenticateWithApiKeyTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp()
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
                $this->normalUser(),
            ],
        ]);
    }

    protected function key(int $user_id = null): ApiKey
    {
        return ApiKey::unguarded(function () use ($user_id) {
            return ApiKey::query()->firstOrCreate([
                'key'        => str_random(),
                'user_id'    => $user_id,
                'created_at' => Carbon::now()
            ]);
        });
    }

    /**
     * @test
     * @expectedException \Flarum\User\Exception\PermissionDeniedException
     */
    public function cannot_authorize_without_key()
    {
        /** @var Client $api */
        $api = $this->app()->getContainer()->make(Client::class);
        $api->setErrorHandler(null);

        $api->send(CreateGroupController::class, new Guest);
    }

    /**
     * @test
     */
    public function master_token_can_authenticate_as_anyone()
    {
        $key = $this->key();

        $request = ServerRequestFactory::fromGlobals()
            ->withAddedHeader('Authorization', "Token {$key->key}; userId=1");

        $pipe = $this->injectAuthorizationPipeline();

        $response = $pipe->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $response->getHeader('X-Authenticated-As')[0]);

        $key = $key->refresh();

        $this->assertNotNull($key->last_activity_at);

        $key->delete();
    }

    /**
     * @test
     */
    public function personal_api_token_cannot_authenticate_as_anyone()
    {
        $user = User::find(2);

        $key = $this->key($user->id);

        $request = ServerRequestFactory::fromGlobals()
            ->withAddedHeader('Authorization', "Token {$key->key}; userId=1");

        $pipe = $this->injectAuthorizationPipeline();

        $response = $pipe->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($user->id, $response->getHeader('X-Authenticated-As')[0]);

        $key = $key->refresh();

        $this->assertNotNull($key->last_activity_at);

        $key->delete();
    }

    /**
     * @test
     */
    public function personal_api_token_authenticates_user()
    {
        $user = User::find(2);

        $key = $this->key($user->id);

        $request = ServerRequestFactory::fromGlobals()
            ->withAddedHeader('Authorization', "Token {$key->key}");

        $pipe = $this->injectAuthorizationPipeline();

        $response = $pipe->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($user->id, $response->getHeader('X-Authenticated-As')[0]);

        $key = $key->refresh();

        $this->assertNotNull($key->last_activity_at);

        $key->delete();
    }

    protected function injectAuthorizationPipeline(): MiddlewarePipe
    {
        app()->resolving('flarum.api.middleware', function ($pipeline) {
            $pipeline->pipe(new class implements MiddlewareInterface {
                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler
                ): ResponseInterface {
                    if ($actor = $request->getAttribute('actor')) {
                        return new Response\EmptyResponse(200, [
                            'X-Authenticated-As' => $actor->id
                        ]);
                    }
                }
            });
        });

        $pipe = app('flarum.api.middleware');

        return $pipe;
    }
}
