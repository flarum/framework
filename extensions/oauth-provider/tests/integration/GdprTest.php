<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration;

use Carbon\Carbon;
use Flarum\OAuthProvider\Data\ConnectedApplications;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Models\AuthCode;
use Flarum\OAuthProvider\Models\Consent;
use Flarum\OAuthProvider\Models\RefreshToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The applications a member connected are personal data. With flarum/gdpr
 * enabled they are part of the member's export, and anonymising or erasing
 * the member disconnects them all.
 */
class GdprTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use InteractsWithOAuth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-gdpr', 'flarum-oauth-provider');

        $later = Carbon::now()->addHour()->toDateTimeString();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'other', 'email' => 'other@machine.local', 'is_email_confirmed' => 1],
            ],
            'oauth_provider_clients' => [
                $this->clientRow('claude', false, extra: ['name' => 'Claude', 'source' => 'dynamic']),
            ],
            'oauth_provider_user_consents' => [
                ['id' => 1, 'user_id' => 2, 'client_id' => 'claude', 'scopes' => '["mcp:read","mcp:write"]', 'revoked' => 0, 'created_at' => Carbon::now()->subDay()->toDateTimeString()],
                ['id' => 2, 'user_id' => 3, 'client_id' => 'claude', 'scopes' => '["mcp:read"]', 'revoked' => 0, 'created_at' => Carbon::now()->toDateTimeString()],
            ],
            'oauth_provider_access_tokens' => [
                ['id' => 'at-2', 'client_id' => 'claude', 'user_id' => 2, 'revoked' => 0, 'expires_at' => $later],
                ['id' => 'at-3', 'client_id' => 'claude', 'user_id' => 3, 'revoked' => 0, 'expires_at' => $later],
            ],
            'oauth_provider_refresh_tokens' => [
                ['id' => 'rt-2', 'access_token_id' => 'at-2', 'revoked' => 0, 'expires_at' => $later],
                ['id' => 'rt-3', 'access_token_id' => 'at-3', 'revoked' => 0, 'expires_at' => $later],
            ],
            'oauth_provider_auth_codes' => [
                ['id' => 'ac-2', 'client_id' => 'claude', 'user_id' => 2, 'revoked' => 0, 'expires_at' => $later],
                ['id' => 'ac-3', 'client_id' => 'claude', 'user_id' => 3, 'revoked' => 0, 'expires_at' => $later],
            ],
        ]);
    }

    private function type(int $userId): ConnectedApplications
    {
        return $this->app()->getContainer()->make(ConnectedApplications::class, ['user' => User::find($userId), 'erasureRequest' => null]);
    }

    private function assertOnlyTheOtherMembersAccessIsLeft(): void
    {
        $this->assertSame([3], Consent::query()->pluck('user_id')->all());
        $this->assertSame(['at-3'], AccessToken::query()->pluck('id')->all());
        $this->assertSame(['rt-3'], RefreshToken::query()->pluck('id')->all());
        $this->assertSame(['ac-3'], AuthCode::query()->pluck('id')->all());
    }

    #[Test]
    public function is_listed_among_the_data_types(): void
    {
        $response = $this->send($this->request('GET', '/api/gdpr-datatypes', ['authenticatedAs' => 1]));

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertContains(ConnectedApplications::class, array_column(json_decode((string) $response->getBody(), true)['data'], 'id'));
    }

    #[Test]
    public function exports_the_applications_the_member_connected(): void
    {
        $export = $this->type(2)->export();

        $this->assertCount(1, $export);
        $applications = json_decode($export[0]['oauth/connected-applications.json'], true);

        $this->assertCount(1, $applications);
        $this->assertSame('Claude', $applications[0]['application']);
        $this->assertSame(['mcp:read', 'mcp:write'], $applications[0]['scopes']);
    }

    #[Test]
    public function exports_nothing_for_a_member_who_connected_nothing(): void
    {
        $this->database()->table('oauth_provider_user_consents')->where('user_id', 2)->delete();

        $this->assertNull($this->type(2)->export());
    }

    #[Test]
    public function anonymising_disconnects_the_members_applications(): void
    {
        $this->type(2)->anonymize();

        $this->assertOnlyTheOtherMembersAccessIsLeft();
    }

    #[Test]
    public function erasing_disconnects_the_members_applications(): void
    {
        $this->type(2)->delete();

        $this->assertOnlyTheOtherMembersAccessIsLeft();
    }
}
