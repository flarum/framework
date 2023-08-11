<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\User\User;
use Illuminate\Http\Request as IlluminateRequest;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RequestUtil
{
    public static function getActor(ServerRequestInterface|SymfonyRequest $request): User
    {
        return self::getActorReference($request)->getActor();
    }

    public static function withActor(ServerRequestInterface|SymfonyRequest $request, User $actor): ServerRequestInterface|SymfonyRequest
    {
        $actorReference = self::getActorReference($request);

        if (! $actorReference) {
            $actorReference = new ActorReference;
            $request = self::setActorReference($request, $actorReference);
        }

        $actorReference->setActor($actor);

        return $request;
    }

    private static function setActorReference(ServerRequestInterface|SymfonyRequest $request, ActorReference $reference): ServerRequestInterface|SymfonyRequest
    {
        if ($request instanceof ServerRequestInterface) {
            $request = $request->withAttribute('actorReference', $reference);
        } else {
            $request->attributes->set('actorReference', $reference);
        }

        return $request;
    }

    private static function getActorReference(ServerRequestInterface|SymfonyRequest $request): ?ActorReference
    {
        if ($request instanceof ServerRequestInterface) {
            return $request->getAttribute('actorReference');
        }

        return $request->attributes->get('actorReference');
    }

    public static function toIlluminate(ServerRequestInterface $request): IlluminateRequest
    {
        $httpFoundationFactory = new HttpFoundationFactory();

        return IlluminateRequest::createFromBase(
            $httpFoundationFactory->createRequest($request)
        );
    }

    public static function toPsr7(SymfonyRequest $request): ServerRequestInterface
    {
        $psrHttpFactory = new PsrHttpFactory(
            new ServerRequestFactory(), new StreamFactory(), new UploadedFileFactory(), new ResponseFactory()
        );

        return $psrHttpFactory->createRequest($request);
    }

    public static function responseToSymfony(\Psr\Http\Message\ResponseInterface $response): SymfonyResponse
    {
        return (new HttpFoundationFactory())->createResponse($response);
    }
}
