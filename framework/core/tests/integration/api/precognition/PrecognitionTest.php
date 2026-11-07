<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\precognition;

use Flarum\Api\PrecognitionThrottler;
use Flarum\Extend;
use Flarum\Group\Event\Saving as GroupSaving;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Event\Saving as UserSaving;
use Flarum\User\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

class PrecognitionTest extends TestCase
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

    protected function precognitive(ServerRequestInterface $request, ?string $only = null): ServerRequestInterface
    {
        $request = $request->withHeader('Precognition', 'true');

        return $only === null ? $request : $request->withHeader('Precognition-Validate-Only', $only);
    }

    protected function updateUsername(string $username, int $as = 1): ServerRequestInterface
    {
        return $this->request('PATCH', '/api/users/2', [
            'authenticatedAs' => $as,
            'json' => ['data' => ['type' => 'users', 'id' => '2', 'attributes' => ['username' => $username]]],
        ]);
    }

    protected function createGroup(array $attributes, int $as = 1): ServerRequestInterface
    {
        return $this->request('POST', '/api/groups', [
            'authenticatedAs' => $as,
            'json' => ['data' => ['type' => 'groups', 'attributes' => $attributes]],
        ]);
    }

    #[Test]
    public function invalid_update_returns_422_with_precognition_header()
    {
        $response = $this->send($this->precognitive($this->updateUsername('not valid!')));

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('true', $response->getHeaderLine('Precognition'));
        $this->assertStringContainsString('Precognition', $response->getHeaderLine('Vary'));
        $this->assertEquals('/data/attributes/username', json_decode((string) $response->getBody(), true)['errors'][0]['source']['pointer']);
    }

    #[Test]
    public function valid_update_returns_204_and_does_not_save()
    {
        $saving = 0;

        $this->app()->getContainer()->make(Dispatcher::class)->listen(UserSaving::class, function () use (&$saving) {
            $saving++;
        });

        $response = $this->send($this->precognitive($this->updateUsername('renamed_user')));

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('true', $response->getHeaderLine('Precognition-Success'));
        $this->assertEquals('true', $response->getHeaderLine('Precognition'));
        $this->assertSame('', (string) $response->getBody());
        $this->assertEquals('normal', User::query()->find(2)->username);
        $this->assertEquals(0, $saving);
    }

    #[Test]
    public function valid_create_returns_204_and_creates_nothing()
    {
        $saving = 0;

        $this->app()->getContainer()->make(Dispatcher::class)->listen(GroupSaving::class, function () use (&$saving) {
            $saving++;
        });

        $count = Group::query()->count();

        $response = $this->send($this->precognitive($this->createGroup([
            'nameSingular' => 'flarumite',
            'namePlural' => 'flarumites',
        ])));

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('true', $response->getHeaderLine('Precognition-Success'));
        $this->assertEquals($count, Group::query()->count());
        $this->assertNull(Group::query()->where('name_singular', 'flarumite')->first());
        $this->assertEquals(0, $saving);
    }

    #[Test]
    public function invalid_create_returns_422()
    {
        $response = $this->send($this->precognitive($this->createGroup(['namePlural' => 'flarumites'])));

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('true', $response->getHeaderLine('Precognition'));
        $this->assertEquals('/data/attributes/nameSingular', json_decode((string) $response->getBody(), true)['errors'][0]['source']['pointer']);
    }

    #[Test]
    public function validate_only_limits_validation_to_listed_fields()
    {
        // nameSingular is required on create but not listed, so it is not validated.
        $response = $this->send($this->precognitive(
            $this->createGroup(['namePlural' => 'flarumites']),
            'namePlural, unknownField'
        ));

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals(0, Group::query()->where('name_plural', 'flarumites')->count());
    }

    #[Test]
    public function validate_only_still_reports_listed_invalid_fields()
    {
        $response = $this->send($this->precognitive($this->updateUsername('not valid!'), 'username'));

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function unauthorized_actor_gets_403()
    {
        $response = $this->send($this->precognitive($this->createGroup([
            'nameSingular' => 'flarumite',
            'namePlural' => 'flarumites',
        ], 2)));

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('true', $response->getHeaderLine('Precognition'));
    }

    #[Test]
    public function non_writable_field_is_forbidden()
    {
        // A normal user cannot change another user's username.
        $response = $this->send($this->precognitive($this->request('PATCH', '/api/users/1', [
            'authenticatedAs' => 2,
            'json' => ['data' => ['type' => 'users', 'id' => '1', 'attributes' => ['username' => 'hijack']]],
        ])));

        $this->assertContains($response->getStatusCode(), [403, 404], (string) $response->getBody());
        $this->assertEquals('admin', User::query()->find(1)->username);
    }

    #[Test]
    public function precognitive_requests_skip_the_endpoint_throttlers()
    {
        $this->extend((new Extend\ThrottleApi)->set('blockGroupCreate', function ($request) {
            if ($request->getAttribute('routeName') === 'groups.create') {
                return true;
            }
        }));

        $attributes = ['nameSingular' => 'flarumite', 'namePlural' => 'flarumites'];

        $response = $this->send($this->precognitive($this->createGroup($attributes)));
        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        $response = $this->send($this->createGroup($attributes));
        $this->assertEquals(429, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function guest_precognitive_requests_get_the_precognition_limiter_not_the_endpoint_throttlers()
    {
        $this->extend((new Extend\ThrottleApi)->set('blockRegistration', function ($request) {
            if ($request->getAttribute('routeName') === 'users.create') {
                return true;
            }
        }));

        $register = fn () => $this->precognitive($this->request('POST', '/api/users', [
            'json' => ['data' => ['type' => 'users', 'attributes' => [
                'username' => 'newcomer',
                'email' => 'newcomer@machine.local',
                'password' => 'too-obscure',
            ]]],
        ])->withAttribute('bypassCsrfToken', true));

        for ($i = 0; $i < 60; $i++) {
            $response = $this->send($register());
            $this->assertEquals(204, $response->getStatusCode(), "request $i: ".$response->getBody());
        }

        $response = $this->send($register());
        $this->assertEquals(429, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('true', $response->getHeaderLine('Precognition'));
        $this->assertEquals(0, User::query()->where('username', 'newcomer')->count());
    }

    #[Test]
    public function precognition_limiter_trips_with_429_per_actor()
    {
        $this->app()->getContainer()->singleton('flarum.api.precognition_throttler', function ($container) {
            return new PrecognitionThrottler(new RateLimiter($container->make(Cache::class)), 2, 60);
        });

        $attributes = ['nameSingular' => 'flarumite', 'namePlural' => 'flarumites'];

        $this->assertEquals(204, $this->send($this->precognitive($this->createGroup($attributes)))->getStatusCode());
        $this->assertEquals(204, $this->send($this->precognitive($this->createGroup($attributes)))->getStatusCode());

        $response = $this->send($this->precognitive($this->createGroup($attributes)));
        $this->assertEquals(429, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('true', $response->getHeaderLine('Precognition'));

        // Another actor has its own budget, and normal requests are not counted.
        $guest = $this->precognitive($this->request('POST', '/api/users', [
            'json' => ['data' => ['type' => 'users', 'attributes' => ['username' => 'newcomer', 'email' => 'newcomer@machine.local', 'password' => 'too-obscure']]],
        ])->withAttribute('bypassCsrfToken', true));
        $this->assertEquals(204, $this->send($guest)->getStatusCode());
        $this->assertEquals(201, $this->send($this->createGroup($attributes))->getStatusCode());
    }

    #[Test]
    public function precognition_header_on_unsupported_route_returns_400_without_running_it()
    {
        $response = $this->send($this->precognitive($this->request('POST', '/api/token', [
            'json' => ['identification' => 'normal', 'password' => 'too-obscure'],
        ])));

        $this->assertEquals(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('true', $response->getHeaderLine('Precognition'));
        $this->assertEquals('precognition_not_supported', json_decode((string) $response->getBody(), true)['errors'][0]['code']);
    }

    #[Test]
    public function precognition_header_on_non_saving_endpoint_changes_nothing()
    {
        $response = $this->send($this->precognitive($this->request('DELETE', '/api/users/2', [
            'authenticatedAs' => 1,
        ])));

        $this->assertEquals(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNotNull(User::query()->find(2));

        $response = $this->send($this->precognitive($this->request('GET', '/api/users/2', [
            'authenticatedAs' => 1,
        ])));

        $this->assertEquals(400, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function token_route_without_precognition_header_is_still_throttled()
    {
        $this->extend((new Extend\ThrottleApi)->set('blockLogin', function ($request) {
            if ($request->getAttribute('routeName') === 'token') {
                return true;
            }
        }));

        $response = $this->send($this->request('POST', '/api/token', [
            'json' => ['identification' => 'normal', 'password' => 'too-obscure'],
        ]));

        $this->assertEquals(429, $response->getStatusCode(), (string) $response->getBody());
        $this->assertFalse($response->hasHeader('Precognition'));
    }

    #[Test]
    public function empty_validate_only_header_validates_every_field()
    {
        $response = $this->send($this->precognitive($this->createGroup(['namePlural' => 'flarumites']), ' '));

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function validate_only_naming_no_field_validates_every_field()
    {
        $response = $this->send($this->precognitive($this->createGroup(['namePlural' => 'flarumites']), 'unknownField, other'));

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('/data/attributes/nameSingular', json_decode((string) $response->getBody(), true)['errors'][0]['source']['pointer']);
    }

    #[Test]
    public function normal_requests_are_unaffected()
    {
        $response = $this->send($this->updateUsername('renamed_user'));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertFalse($response->hasHeader('Precognition'));
        $this->assertEquals('renamed_user', User::query()->find(2)->username);
    }
}
