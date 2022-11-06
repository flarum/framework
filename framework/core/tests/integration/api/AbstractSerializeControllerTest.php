<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Flarum\Api\Controller\AbstractSerializeController;
use Flarum\Extend;
use Flarum\Testing\integration\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\SerializerInterface;

class AbstractSerializeControllerTest extends TestCase
{
    public function test_missing_serializer_class_throws_exception()
    {
        $this->extend(
            (new Extend\Routes('api'))
                ->get('/dummy-serialize', 'dummy-serialize', DummySerializeController::class)
        );

        $response = $this->send(
            $this->request('GET', '/api/dummy-serialize')
        );

        $json = json_decode((string) $response->getBody(), true);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringStartsWith('InvalidArgumentException: Serializer required for controller: '.DummySerializeController::class, $json['errors'][0]['detail']);
    }
}

class DummySerializeController extends AbstractSerializeController
{
    public $serializer = null;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        return [];
    }

    protected function createElement($data, SerializerInterface $serializer)
    {
        return $data;
    }
}
