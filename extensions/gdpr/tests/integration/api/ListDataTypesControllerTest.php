<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Tests\integration\Api;

use Flarum\Api\Endpoint\Index;
use Flarum\Extend;
use Flarum\Gdpr\Api\Resource\DataTypeResource;
use Flarum\Gdpr\Data\Forum;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ListDataTypesControllerTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);

        $this->extension('flarum-gdpr');
    }

    #[Test]
    public function non_admin_cannot_list_types()
    {
        $response = $this->send(
            $this->request('GET', '/api/gdpr-datatypes', ['authenticatedAs' => 2])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function admin_can_list_types()
    {
        $response = $this->send(
            $this->request('GET', '/api/gdpr-datatypes', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(7, $body['data']);

        $this->assertEquals(Forum::class, $body['data'][0]['id']);
        $this->assertEquals(Forum::exportDescription(), $body['data'][0]['attributes']['exportDescription']);
        $this->assertEquals(Forum::anonymizeDescription(), $body['data'][0]['attributes']['anonymizeDescription']);
        $this->assertEquals(Forum::deleteDescription(), $body['data'][0]['attributes']['deleteDescription']);
        $this->assertNull($body['data'][0]['attributes']['extension']);
    }

    #[Test]
    #[DataProvider('pageParameters')]
    public function rejects_a_page_of_types_because_the_list_is_not_paginated(string $name, string $value)
    {
        $response = $this->send(
            $this->request('GET', '/api/gdpr-datatypes', ['authenticatedAs' => 1])
                ->withQueryParams(['page' => [$name => $value]])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(400, $response->getStatusCode(), $body);
        $this->assertEquals("page[$name]", json_decode($body, true)['errors'][0]['source']['parameter'] ?? null);
    }

    public static function pageParameters(): array
    {
        return [
            'limit' => ['limit', '1'],
            'offset' => ['offset', '1'],
            'number' => ['number', '2'],
            'near' => ['near', '2'],
            'size' => ['size', '2'],
        ];
    }

    #[Test]
    #[DataProvider('pagesFromTheStart')]
    public function lists_every_type_when_asked_to_start_from_the_beginning(array $page)
    {
        $response = $this->send(
            $this->request('GET', '/api/gdpr-datatypes', ['authenticatedAs' => 1])
                ->withQueryParams(['page' => $page])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertCount(7, json_decode($body, true)['data']);
    }

    public static function pagesFromTheStart(): array
    {
        return [
            'offset zero' => [['offset' => '0']],
            'first page by number' => [['number' => '1']],
            'empty limit' => [['limit' => '']],
            'without a total' => [['total' => '0']],
        ];
    }

    #[Test]
    public function refuses_a_page_before_the_endpoint_runs_its_hooks()
    {
        $ran = false;

        $this->extend(
            (new Extend\ApiResource(DataTypeResource::class))
                ->endpoint(Index::class, function (Index $endpoint) use (&$ran): Index {
                    return $endpoint->before(function () use (&$ran) {
                        $ran = true;
                    });
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/gdpr-datatypes', ['authenticatedAs' => 1])
                ->withQueryParams(['page' => ['limit' => '2']])
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($ran);
    }
}
