<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\security\precognition;

require_once __DIR__.'/PrecognitionTestHelpers.php';

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class HeaderAndRouteTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use PrecognitionTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['flarum-tags', 'flarum-flags', 'flarum-gdpr', 'flarum-messages', 'flarum-likes', 'flarum-subscriptions', 'flarum-approval'] as $extension) {
            $this->extension($extension);
        }

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Public', 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'created_at' => Carbon::now()],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
            ],
            'gdpr_erasure' => [
                ['id' => 1, 'user_id' => 2, 'verification_token' => 'abc123', 'status' => 'awaiting_user_confirmation', 'reason' => 'bye', 'created_at' => Carbon::now()],
            ],
            'dialogs' => [['id' => 1, 'type' => 'direct']],
            'dialog_user' => [['dialog_id' => 1, 'user_id' => 2, 'joined_at' => Carbon::now()]],
        ]);
    }

    protected function createGroupRequest(): \Psr\Http\Message\ServerRequestInterface
    {
        return $this->jsonRequest('POST', '/api/groups', ['data' => ['type' => 'groups', 'attributes' => [
            'nameSingular' => 'precog', 'namePlural' => 'precogs',
        ]]], 1);
    }

    public static function acceptedValues(): array
    {
        return [
            'TRUE' => ['TRUE'],
            'True' => ['True'],
            'padded' => ['  true  '],
            'tab' => ["\ttrue"],
        ];
    }

    #[Test]
    #[DataProvider('acceptedValues')]
    public function case_and_whitespace_variants_are_precognitive_and_write_nothing(string $value)
    {
        $response = $this->send($this->createGroupRequest()->withHeader('Precognition', $value));

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals(0, Group::query()->where('name_singular', 'precog')->count());
    }

    /**
     * A client (or a proxy merging/duplicating headers) that sends the header
     * twice gets `Precognition: true,true`. Every value is `true`, so the
     * request is precognitive: validated, never written.
     */
    #[Test]
    public function duplicated_precognition_header_is_precognitive_and_writes_nothing()
    {
        $response = $this->send($this->createGroupRequest()
            ->withHeader('Precognition', 'true')
            ->withAddedHeader('Precognition', 'true'));

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals(0, Group::query()->where('name_singular', 'precog')->count(), 'HTTP '.$response->getStatusCode().': group was created');
    }

    public static function malformedValues(): array
    {
        return [
            '1' => ['1'],
            'yes' => ['yes'],
            'true, false' => ['true, false'],
            'true,' => ['true,'],
        ];
    }

    /**
     * Any non-empty Precognition value other than "true" signals validate-only
     * intent but cannot be honoured: it fails closed with a 400 and the
     * endpoint never runs.
     */
    #[Test]
    #[DataProvider('malformedValues')]
    public function unrecognised_precognition_value_fails_closed_without_writing(string $value)
    {
        $response = $this->send($this->createGroupRequest()->withHeader('Precognition', $value));

        $this->assertEquals(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('precognition_not_supported', json_decode((string) $response->getBody(), true)['errors'][0]['code']);
        $this->assertEquals(0, Group::query()->where('name_singular', 'precog')->count(), 'HTTP '.$response->getStatusCode().': group was created');
    }

    /**
     * [method, path, actor, body, uploadedFiles?]
     */
    public static function unsupportedRoutes(): array
    {
        return [
            'GET show' => ['GET', '/api/users/2', 2, null],
            'GET index' => ['GET', '/api/discussions', 2, null],
            'DELETE user' => ['DELETE', '/api/users/2', 1, null],
            'DELETE post' => ['DELETE', '/api/posts/1', 1, null],
            'DELETE discussion' => ['DELETE', '/api/discussions/1', 1, null],
            'POST token' => ['POST', '/api/token', null, ['identification' => 'normal', 'password' => 'too-obscure']],
            'POST forgot' => ['POST', '/api/forgot', null, ['email' => 'normal@machine.local']],
            'POST send confirmation' => ['POST', '/api/users/2/send-confirmation', 2, null],
            'DELETE avatar' => ['DELETE', '/api/users/2/avatar', 2, null],
            'DELETE notification prefs' => ['DELETE', '/api/users/2/notification-preferences', 2, null],
            'POST mail test' => ['POST', '/api/mail/test', 1, null],
            'POST cache clear' => ['DELETE', '/api/cache', 1, null],
            'POST notifications read all' => ['POST', '/api/notifications/read', 2, null],
            'POST gdpr cancel' => ['POST', '/api/user-erasure-requests/1/cancel', 2, null],
            'POST gdpr export' => ['POST', '/api/gdpr/export', 2, null],
            'POST dialogs read' => ['POST', '/api/dialogs/read', 2, null],
            'DELETE flags' => ['DELETE', '/api/posts/1/flags', 1, null],
            'POST settings' => ['POST', '/api/settings', 1, ['forum_title' => 'pwned']],
            'POST permission' => ['POST', '/api/permission', 1, ['permission' => 'viewForum', 'groupIds' => []]],
            'PATCH extension' => ['PATCH', '/api/extensions/flarum-tags', 1, ['enabled' => false]],
            'POST method override DELETE' => ['POST', '/api/posts/1', 1, null, ['X-HTTP-Method-Override' => 'DELETE']],
        ];
    }

    #[Test]
    #[DataProvider('unsupportedRoutes')]
    public function precognition_on_unsupported_route_is_400_and_changes_nothing(string $method, string $path, ?int $as, ?array $json, array $headers = [])
    {
        $request = $this->precognitive($this->jsonRequest($method, $path, $json, $as));

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $db = $this->databaseFingerprint();
        $files = $this->fileFingerprint();

        $response = $this->send($request);

        // A route that doesn't exist (404/405) is fine too: what matters is it didn't run.
        $this->assertContains($response->getStatusCode(), [400, 404, 405], (string) $response->getBody());
        $this->assertSame([], array_keys(array_diff_assoc($this->databaseFingerprint(), $db)), 'tables changed');
        $this->assertSame($files, $this->fileFingerprint());
    }

    #[Test]
    public function precognition_on_avatar_upload_is_400_and_writes_no_file()
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $tmp = tempnam(sys_get_temp_dir(), 'av');
        file_put_contents($tmp, $png);

        $request = $this->precognitive($this->request('POST', '/api/users/2/avatar', ['authenticatedAs' => 2]))
            ->withUploadedFiles(['avatar' => new UploadedFile($tmp, strlen($png), UPLOAD_ERR_OK, 'a.png', 'image/png')]);

        $files = $this->fileFingerprint();
        $avatar = User::query()->find(2)->avatar_url;
        $response = $this->send($request);

        $this->assertEquals(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame($avatar, User::query()->find(2)->avatar_url);
        $this->assertSame($files, $this->fileFingerprint());
    }

    /**
     * Session-authenticated (cookie) precognitive requests still need the CSRF
     * token: CheckCsrfToken runs before HandlePrecognitiveRequests.
     */
    #[Test]
    public function session_auth_precognition_still_requires_csrf()
    {
        $request = $this->precognitive($this->jsonRequest('PATCH', '/api/users/2', [
            'data' => ['type' => 'users', 'id' => '2', 'attributes' => ['username' => 'bad name!']],
        ], 2))->withoutAttribute('bypassCsrfToken');

        $response = $this->send($request);

        $this->assertEquals(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('csrf_token_mismatch', $this->body($response)['errors'][0]['code'] ?? null);
    }

    /**
     * Validate-Only narrows validation but not authorization: a non-writable
     * field in the body is refused even when it isn't listed.
     */
    #[Test]
    public function validate_only_does_not_skip_field_authorization()
    {
        $response = $this->send($this->precognitive($this->jsonRequest('PATCH', '/api/users/2', [
            'data' => ['type' => 'users', 'id' => '2', 'attributes' => ['isEmailConfirmed' => true, 'displayName' => 'x']],
        ], 2), 'displayName'));

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
    }

    /**
     * Precognitive 422 bodies carry exactly what the normal 422 carries, and no
     * debug/internal detail.
     */
    #[Test]
    public function precognitive_422_body_equals_normal_422_body()
    {
        $body = ['data' => ['type' => 'groups', 'attributes' => ['nameSingular' => str_repeat('x', 300), 'namePlural' => '']]];

        $normal = $this->send($this->jsonRequest('POST', '/api/groups', $body, 1));
        $precog = $this->send($this->precognitive($this->jsonRequest('POST', '/api/groups', $body, 1)));

        $this->assertEquals(422, $normal->getStatusCode());
        $this->assertEquals(422, $precog->getStatusCode());
        $this->assertEquals((string) $normal->getBody(), (string) $precog->getBody());
        $this->assertStringNotContainsString('trace', (string) $precog->getBody());
        $this->assertStringNotContainsString('.php', (string) $precog->getBody());

        $extra = array_diff(array_map('strtolower', array_keys($precog->getHeaders())), array_map('strtolower', array_keys($normal->getHeaders())));
        // The nested error handler renders the 422 inside the session/assets middleware, so the
        // precognitive error also carries the headers a successful response carries; nothing else.
        $this->assertSame([], array_values(array_diff($extra, ['precognition', 'vary', 'x-csrf-token', 'set-cookie', 'x-flarum-assets-revision'])));
    }

    /**
     * A huge Validate-Only list (and a body full of unknown fields) is handled
     * quickly and doesn't error.
     */
    #[Test]
    public function huge_validate_only_list_is_cheap()
    {
        $names = implode(',', array_map(fn ($i) => "field$i", range(1, 50000)));
        $attributes = array_fill_keys(array_map(fn ($i) => "junk$i", range(1, 5000)), 'x') + ['nameSingular' => 'a', 'namePlural' => 'b'];

        $start = microtime(true);
        $response = $this->send($this->precognitive($this->jsonRequest('POST', '/api/groups', ['data' => ['type' => 'groups', 'attributes' => $attributes]], 1), $names.',nameSingular'));
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(500, $response->getStatusCode(), (string) $response->getBody());
        $this->assertLessThan(3.0, $elapsed, "took {$elapsed}s");
    }
}
