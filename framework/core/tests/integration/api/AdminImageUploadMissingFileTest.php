<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class AdminImageUploadMissingFileTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    public static function endpoints(): array
    {
        return [
            ['/api/logo', 'logo'],
            ['/api/favicon', 'favicon'],
        ];
    }

    #[Test]
    #[DataProvider('endpoints')]
    public function text_field_instead_of_file_part(string $path, string $prefix): void
    {
        $request = $this->request('POST', $path, ['authenticatedAs' => 1])
            ->withUploadedFiles([])
            ->withParsedBody([$prefix => 'undefined'])
            ->withHeader('content-type', 'multipart/form-data; boundary=----WebKitFormBoundaryTEST');

        $response = $this->send($request);

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());

        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals('validation_error', $body['errors'][0]['code'] ?? null);
        $this->assertNotEmpty($body['errors'][0]['detail'] ?? null);
    }
}
