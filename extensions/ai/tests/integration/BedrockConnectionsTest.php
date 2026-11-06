<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Tests\integration;

use Flarum\Ai\Exception\NotConfiguredException;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Laravel\Ai\AiManager;
use PHPUnit\Framework\Attributes\Test;

/**
 * Amazon Bedrock connections the admin sets up: a region instead of an
 * address, and a Bedrock API key or the server's own AWS credentials.
 */
class BedrockConnectionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected const KEY = 'ABSKbedrock-secret-0123456789';

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-ai');
        $this->prepareDatabase(['users' => [$this->normalUser()]]);
    }

    protected function save(array $connections): int
    {
        return $this->send($this->request('POST', '/api/settings', [
            'authenticatedAs' => 1,
            'json' => ['flarum-ai.connections' => json_encode($connections)],
        ]))->getStatusCode();
    }

    protected function stored(): array
    {
        return json_decode((string) $this->database()->table('settings')->where('key', 'flarum-ai.connections')->value('value'), true) ?? [];
    }

    protected function storedKeys(): array
    {
        return $this->database()->table('ai_connection_keys')->pluck('key', 'connection')->all();
    }

    #[Test]
    public function a_bedrock_connection_is_saved_with_its_region_key_and_guardrail(): void
    {
        $this->assertSame(204, $this->save(['aws' => [
            'driver' => 'bedrock',
            'region' => ' eu-central-1 ',
            'guardrail_id' => ' gr-abc123 ',
            'model' => 'eu.anthropic.claude-x',
            'key' => self::KEY,
        ]]));

        $this->assertSame(['aws' => [
            'driver' => 'bedrock',
            'region' => 'eu-central-1',
            'model' => 'eu.anthropic.claude-x',
            'guardrail_id' => 'gr-abc123',
            'guardrail_version' => 'DRAFT',
        ]], $this->stored());
        $this->assertSame(['aws' => self::KEY], $this->storedKeys());
    }

    #[Test]
    public function without_a_key_it_uses_the_servers_aws_credentials_where_config_php_allows(): void
    {
        $this->config('ai.bedrock_server_credentials', true);

        $this->assertSame(204, $this->save(['aws' => ['driver' => 'bedrock', 'region' => 'us-east-1']]));

        $this->assertSame([], $this->storedKeys());

        $this->setting('flarum-ai.connections', json_encode($this->stored()));
        $provider = $this->app()->getContainer()->make(AiManager::class)->instance('aws');

        $this->assertSame([], $provider->providerCredentials());
        $this->assertSame('us-east-1', $provider->additionalConfiguration()['region']);
        $this->assertTrue($provider->additionalConfiguration()['use_default_credential_provider']);
    }

    #[Test]
    public function the_servers_aws_credentials_are_not_the_admins_to_use_unless_config_php_says(): void
    {
        // Forum admins aren't always who runs the server, and its credentials can reach further.
        $this->assertSame(422, $this->save(['aws' => ['driver' => 'bedrock', 'region' => 'us-east-1']]));
    }

    #[Test]
    public function a_connection_saved_without_a_key_isnt_built_unless_config_php_says(): void
    {
        // The AWS SDK looks for the server's credentials, and its Bedrock API
        // key in AWS_BEARER_TOKEN_BEDROCK, whenever it's given none.
        $this->setting('flarum-ai.connections', json_encode(['aws' => ['driver' => 'bedrock', 'region' => 'us-east-1']]));

        $this->expectException(NotConfiguredException::class);

        $this->app()->getContainer()->make(AiManager::class)->instance('aws');
    }

    #[Test]
    public function a_key_laravel_ai_would_drop_counts_as_none(): void
    {
        $this->assertSame(422, $this->save(['aws' => ['driver' => 'bedrock', 'region' => 'us-east-1', 'key' => '0']]));
    }

    #[Test]
    public function a_stored_key_laravel_ai_would_drop_counts_as_none(): void
    {
        $this->setting('flarum-ai.connections', json_encode(['aws' => ['driver' => 'bedrock', 'region' => 'us-east-1']]));
        $this->prepareDatabase(['ai_connection_keys' => [['connection' => 'aws', 'key' => '0', 'updated_at' => date('Y-m-d H:i:s')]]]);

        $this->expectException(NotConfiguredException::class);

        $this->app()->getContainer()->make(AiManager::class)->instance('aws');
    }

    #[Test]
    public function with_a_key_the_servers_credentials_are_never_reached_for(): void
    {
        $this->setting('flarum-ai.connections', json_encode(['aws' => ['driver' => 'bedrock', 'region' => 'us-east-1']]));
        $this->prepareDatabase(['ai_connection_keys' => [['connection' => 'aws', 'key' => self::KEY, 'updated_at' => date('Y-m-d H:i:s')]]]);

        $provider = $this->app()->getContainer()->make(AiManager::class)->instance('aws');

        $this->assertFalse($provider->additionalConfiguration()['use_default_credential_provider']);
    }

    #[Test]
    public function a_config_php_guardrail_version_given_as_a_number_is_shown(): void
    {
        $this->config('ai.connections.host', ['driver' => 'bedrock', 'region' => 'us-east-1', 'guardrail_id' => 'gr1', 'guardrail_version' => 3]);

        $page = (string) $this->send($this->request('GET', '/admin', ['authenticatedAs' => 1]))->getBody();
        preg_match('/<script id="flarum-json-payload" type="application\/json">(.+?)<\/script>/s', $page, $matches);
        $payload = json_decode(html_entity_decode($matches[1]), true)['flarum-ai'];

        $this->assertSame('3', $payload['lockedConnections']['host']['guardrail_version']);
    }

    #[Test]
    public function config_php_connections_choose_their_own_credentials(): void
    {
        $this->config('ai.connections.host', ['driver' => 'bedrock', 'region' => 'us-east-1']);

        $provider = $this->app()->getContainer()->make(AiManager::class)->instance('host');

        $this->assertTrue($provider->additionalConfiguration()['use_default_credential_provider']);
    }

    #[Test]
    public function its_key_reaches_the_provider_as_a_bedrock_api_key(): void
    {
        $this->setting('flarum-ai.connections', json_encode(['aws' => ['driver' => 'bedrock', 'region' => 'eu-west-1', 'guardrail_id' => 'gr1', 'guardrail_version' => '2']]));
        $this->prepareDatabase(['ai_connection_keys' => [['connection' => 'aws', 'key' => self::KEY, 'updated_at' => date('Y-m-d H:i:s')]]]);

        $provider = $this->app()->getContainer()->make(AiManager::class)->instance('aws');

        $this->assertSame(['key' => self::KEY], $provider->providerCredentials());
        $this->assertSame('eu-west-1', $provider->additionalConfiguration()['region']);
    }

    #[Test]
    public function region_and_guardrail_version_are_checked(): void
    {
        $this->config('ai.bedrock_server_credentials', true);

        $this->assertSame(422, $this->save(['aws' => ['driver' => 'bedrock']]));
        $this->assertSame(422, $this->save(['aws' => ['driver' => 'bedrock', 'region' => 'Frankfurt']]));
        $this->assertSame(422, $this->save(['aws' => ['driver' => 'bedrock', 'region' => 'eu-central-1', 'guardrail_id' => 'gr1', 'guardrail_version' => 'latest']]));
        $this->assertSame(422, $this->save(['aws' => ['driver' => 'bedrock', 'region' => 'eu-central-1', 'guardrail_id' => 'has spaces']]));
        $this->assertSame(204, $this->save(['aws' => ['driver' => 'bedrock', 'region' => 'us-gov-west-1', 'guardrail_id' => 'arn:aws:bedrock:us-gov-west-1:123456789012:guardrail/abc123', 'guardrail_version' => '3']]));
        $this->assertSame([], $this->storedKeys());
    }

    #[Test]
    public function an_address_means_nothing_to_bedrock_and_isnt_kept(): void
    {
        $this->config('ai.bedrock_server_credentials', true);

        $this->assertSame(204, $this->save(['aws' => ['driver' => 'bedrock', 'region' => 'eu-central-1', 'url' => 'https://example.test']]));

        $this->assertSame(['aws' => ['driver' => 'bedrock', 'region' => 'eu-central-1']], $this->stored());
    }

    #[Test]
    public function a_key_is_kept_only_while_the_region_is_unchanged(): void
    {
        $this->assertSame(204, $this->save(['aws' => ['driver' => 'bedrock', 'region' => 'eu-central-1', 'key' => self::KEY]]));

        $this->assertSame(204, $this->save(['aws' => ['driver' => 'bedrock', 'region' => 'eu-central-1', 'model' => 'x']]));
        $this->assertSame(['aws' => self::KEY], $this->storedKeys());

        $this->assertSame(422, $this->save(['aws' => ['driver' => 'bedrock', 'region' => 'us-east-1']]));
        $this->assertSame(204, $this->save(['aws' => ['driver' => 'bedrock', 'region' => 'us-east-1', 'key' => 'ABSKother']]));
        $this->assertSame(['aws' => 'ABSKother'], $this->storedKeys());
    }

    #[Test]
    public function the_admin_page_offers_bedrock_when_the_aws_sdk_is_installed(): void
    {
        $page = (string) $this->send($this->request('GET', '/admin', ['authenticatedAs' => 1]))->getBody();
        preg_match('/<script id="flarum-json-payload" type="application\/json">(.+?)<\/script>/s', $page, $matches);
        $payload = json_decode(html_entity_decode($matches[1]), true)['flarum-ai'];

        $this->assertContains('bedrock', $payload['drivers']);
        $this->assertNotContains('bedrock', $payload['keyOptional']);
        $this->assertSame(['bedrock'], $payload['regionRequired']);
        $this->assertTrue($payload['awsSdk']);
        $this->assertSame([], $payload['defaultModels']);
    }
}
