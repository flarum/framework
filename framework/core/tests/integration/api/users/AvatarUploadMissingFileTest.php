<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class AvatarUploadMissingFileTest extends TestCase
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

    /**
     * Reproduces the production condition: the client posted a well-formed
     * multipart body in which "avatar" is a plain text field rather than a file
     * part, so PHP populates $_POST but leaves $_FILES empty.
     */
    #[Test]
    public function text_field_instead_of_file_part(): void
    {
        $request = $this->request('POST', '/api/users/2/avatar', ['authenticatedAs' => 2])
            ->withUploadedFiles([])
            ->withParsedBody(['avatar' => 'undefined'])
            ->withHeader('content-type', 'multipart/form-data; boundary=----WebKitFormBoundaryTEST');

        $response = $this->send($request);

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());

        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals('/data/attributes/avatar', $body['errors'][0]['source']['pointer'] ?? null);
        $this->assertEquals('validation_error', $body['errors'][0]['code'] ?? null);
        $this->assertNotEmpty($body['errors'][0]['detail'] ?? null);
    }
}
