<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration\console;

use Carbon\Carbon;
use Flarum\OAuthProvider\Models\Client;
use Flarum\OAuthProvider\Tests\integration\InteractsWithOAuth;
use Flarum\Testing\integration\ConsoleTestCase;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\User\User;
use Illuminate\Console\Scheduling\Schedule;
use PHPUnit\Framework\Attributes\Test;

class PruneClientsTest extends ConsoleTestCase
{
    use RetrievesAuthorizedUsers;
    use InteractsWithOAuth;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-oauth-provider');

        $old = ['created_at' => Carbon::now()->subDays(31)->toDateTimeString()];
        $recent = ['created_at' => Carbon::now()->subDays(29)->toDateTimeString()];

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            'oauth_provider_clients' => [
                $this->clientRow('old-dynamic', false, extra: ['source' => Client::SOURCE_DYNAMIC, ...$old]),
                $this->clientRow('https://app.example.com/client.json', false, extra: ['source' => Client::SOURCE_METADATA_DOCUMENT, ...$old]),
                $this->clientRow('recent-dynamic', false, extra: ['source' => Client::SOURCE_DYNAMIC, ...$recent]),
                $this->clientRow('old-admin', false, extra: $old),
                $this->clientRow('old-consented', false, extra: ['source' => Client::SOURCE_DYNAMIC, ...$old]),
                $this->clientRow('old-with-token', false, extra: ['source' => Client::SOURCE_DYNAMIC, ...$old]),
            ],
            'oauth_provider_user_consents' => [
                ['user_id' => 2, 'client_id' => 'old-consented', 'scopes' => '["openid"]', 'revoked' => 1, 'created_at' => $old['created_at'], 'updated_at' => $old['created_at']],
            ],
            'oauth_provider_access_tokens' => [
                ['id' => 'token-1', 'client_id' => 'old-with-token', 'user_id' => 2, 'scopes' => '["openid"]', 'revoked' => 1, 'expires_at' => $old['created_at'], 'created_at' => $old['created_at']],
            ],
        ]);
    }

    #[Test]
    public function unused_self_registered_clients_older_than_30_days_are_deleted(): void
    {
        $output = $this->runCommand(['command' => 'oauth:prune-clients']);

        $this->assertStringContainsString('Deleted 2', $output);
        $this->assertEqualsCanonicalizing(
            ['recent-dynamic', 'old-admin', 'old-consented', 'old-with-token'],
            Client::query()->pluck('id')->all()
        );
    }

    #[Test]
    public function pruning_is_scheduled_daily(): void
    {
        $this->app();

        $events = array_filter(
            $this->app()->getContainer()->make(Schedule::class)->events(),
            fn ($event) => str_contains((string) $event->command, 'oauth:prune-clients')
        );

        $this->assertCount(1, $events);
        $this->assertSame('0 0 * * *', array_values($events)[0]->expression);
    }
}
