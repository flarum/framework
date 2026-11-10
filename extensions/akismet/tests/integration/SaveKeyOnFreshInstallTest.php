<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet\Tests\integration;

use Flarum\Akismet\Akismet;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * On a fresh install no api_key setting exists yet, so the container resolves
 * Akismet with a null key. The service must tolerate that: the settings save
 * that stores the very first key resolves Akismet *before* the key is written
 * (ValidateApiKey takes it as a constructor dependency), so a non-nullable
 * constructor made it impossible to ever save one (flarum/framework#5029).
 *
 * Deliberately uses the real provider rather than the Guzzle fake, because the
 * failure is in construction, not in any request.
 */
class SaveKeyOnFreshInstallTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-flags', 'flarum-approval', 'flarum-akismet');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    #[Test]
    public function akismet_resolves_when_no_api_key_has_ever_been_saved()
    {
        $this->app();

        // No flarum-akismet.api_key row exists at all.
        $akismet = $this->app()->getContainer()->make(Akismet::class);

        $this->assertFalse($akismet->isConfigured());
    }

    #[Test]
    public function saving_the_first_api_key_does_not_error()
    {
        $response = $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => ['flarum-akismet.api_key' => 'a-first-key'],
            ])
        );

        $this->assertNotEquals(500, $response->getStatusCode(), (string) $response->getBody());
    }
}
