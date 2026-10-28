<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\resource_schemas;

use Flarum\Group\Group;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ShowTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'searcher', 'email' => 'searcher@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 40, 'name_singular' => 'Searcher', 'name_plural' => 'Searchers'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 40],
            ],
            'group_permission' => [
                ['permission' => 'searchUsers', 'group_id' => 40],
            ],
        ]);
    }

    #[Test]
    public function admin_can_describe_users()
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 1])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $json = json_decode($body, true);

        $this->assertSame('resource-schemas', $json['data']['type']);
        $this->assertSame('users', $json['data']['id']);
        $this->assertSame(1, $json['data']['attributes']['schemaVersion']);
        $this->assertNotEmpty($response->getHeaderLine('ETag'));
        $this->assertStringContainsString('private', $response->getHeaderLine('Cache-Control'));
    }

    #[Test]
    public function normal_user_without_search_users_is_forbidden()
    {
        // Members hold `searchUsers` by default (seed migration).
        $this->database()->table('group_permission')
            ->where('permission', 'searchUsers')
            ->where('group_id', Group::MEMBER_ID)
            ->delete();

        // With sign-up open, `UserResource`'s Create endpoint is visible to
        // every actor, which the D2 "Index or Create" gate would accept.
        $this->app()->getContainer()->make(SettingsRepositoryInterface::class)->set('allow_sign_up', false);

        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 2])
        );

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function user_in_a_group_with_search_users_can_describe_users()
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 3])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertSame('users', json_decode($body, true)['data']['id']);
    }

    #[Test]
    public function guest_is_unauthenticated_and_gets_no_locale_cookie()
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/users')
        );

        $this->assertEquals(401, $response->getStatusCode(), (string) $response->getBody());

        foreach ($response->getHeader('Set-Cookie') as $cookie) {
            $this->assertStringNotContainsString('locale=', $cookie);
        }
    }

    #[Test]
    public function a_resource_that_is_not_describable_is_not_found()
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/discussions', ['authenticatedAs' => 1])
        );

        $this->assertEquals(404, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function an_unknown_type_is_not_found()
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/nope', ['authenticatedAs' => 1])
        );

        $this->assertEquals(404, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function a_matching_if_none_match_gets_not_modified()
    {
        $first = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $first->getStatusCode(), (string) $first->getBody());

        $etag = $first->getHeaderLine('ETag');

        $this->assertNotEmpty($etag);

        $second = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 1])
                ->withHeader('If-None-Match', $etag)
        );

        $this->assertEquals(304, $second->getStatusCode());
        $this->assertSame('', (string) $second->getBody());
        $this->assertSame($etag, $second->getHeaderLine('ETag'));
    }

    #[Test]
    public function a_guest_is_unauthenticated_for_any_id()
    {
        // No probing which types exist or are describable without logging in.
        foreach (['nope', 'discussions', 'users'] as $id) {
            $response = $this->send(
                $this->request('GET', "/api/resource-schemas/$id")
            );

            $this->assertEquals(401, $response->getStatusCode(), "[$id] ".$response->getBody());
        }
    }

    #[Test]
    public function a_class_name_id_is_not_found()
    {
        // `JsonApi::getResource()` also accepts a class name; only a type is an id.
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/'.rawurlencode(\Flarum\Api\Resource\UserResource::class), ['authenticatedAs' => 1])
        );

        $this->assertEquals(404, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function a_non_matching_if_none_match_gets_the_document()
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 1])
                ->withHeader('If-None-Match', '"not-the-etag"')
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('users', json_decode((string) $response->getBody(), true)['data']['id']);
    }

    #[Test]
    public function a_weak_or_wildcard_if_none_match_gets_not_modified()
    {
        $etag = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 1])
        )->getHeaderLine('ETag');

        foreach (['W/'.$etag, '*', '"other", '.$etag] as $header) {
            $response = $this->send(
                $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 1])
                    ->withHeader('If-None-Match', $header)
            );

            $this->assertEquals(304, $response->getStatusCode(), "If-None-Match: $header");
        }
    }

    #[Test]
    public function not_modified_is_private()
    {
        $etag = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 1])
        )->getHeaderLine('ETag');

        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 1])
                ->withHeader('If-None-Match', $etag)
        );

        $this->assertEquals(304, $response->getStatusCode());
        $this->assertSame('private', $response->getHeaderLine('Cache-Control'));
    }

    #[Test]
    public function admin_and_member_etags_differ()
    {
        $admin = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 1])
        );
        $member = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 2])
        );

        $this->assertEquals(200, $admin->getStatusCode(), (string) $admin->getBody());
        $this->assertEquals(200, $member->getStatusCode(), (string) $member->getBody());
        $this->assertNotSame($admin->getHeaderLine('ETag'), $member->getHeaderLine('ETag'));
    }
}
