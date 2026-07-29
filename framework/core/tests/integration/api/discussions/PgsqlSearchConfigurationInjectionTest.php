<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PgsqlSearchConfigurationInjectionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->database()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Postgres-specific filter path.');
        }

        $this->database()->rollBack();

        $this->database()->table('discussions')->insert($this->rowsThroughFactory(Discussion::class, [
            ['id' => 1, 'title' => 'hello world', 'user_id' => 1],
        ]));
        $this->database()->table('posts')->insert($this->rowsThroughFactory(Post::class, [
            ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'content' => '<t><p>hello world body</p></t>'],
        ]));

        $this->database()->beginTransaction();

        $this->populateDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->database()->table('discussions')->delete();
        $this->database()->table('posts')->delete();

        // Reset the setting in case the post-condition assertion was hit before reset.
        $this->database()->table('settings')->where('key', 'pgsql_search_configuration')->update(['value' => 'english']);
    }

    #[Test]
    public function baseline_default_search_config_returns_200(): void
    {
        // Sanity check: with the default config, a search succeeds.
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams(['filter' => ['q' => 'hello']])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function malicious_search_config_is_not_interpolated_into_sql(): void
    {
        // Admin (or anyone who can write to the settings table) attempts to inject
        // SQL via the pgsql_search_configuration setting.
        $payload = "english') || pg_sleep(0) || ('";

        /** @var \Flarum\Settings\SettingsRepositoryInterface $settings */
        $settings = $this->app()->getContainer()->make(\Flarum\Settings\SettingsRepositoryInterface::class);
        $settings->set('pgsql_search_configuration', $payload);

        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams(['filter' => ['q' => 'hello']])
        );

        $body = (string) $response->getBody();

        // Under the original vulnerability, the payload was concatenated into
        // SQL as code: Postgres parsed `tsvector || pg_sleep(...)` and raised
        // SQLSTATE 42883 "operator does not exist: tsvector || void".
        //
        // Under the fix, the payload is bound as a parameter and cast to
        // `regconfig`. Postgres rejects it with SQLSTATE 42602 "invalid name
        // syntax" — the value reached the DB as data, not code.
        //
        // We assert on the SQLSTATE, which is unambiguous: 42883 ↔ injection
        // open, 42602 ↔ injection closed.
        $this->assertStringNotContainsString(
            '42883',
            $body,
            "Postgres tried to evaluate the payload as SQL (operator-does-not-exist error). Injection still possible.\nResponse body: $body"
        );
        $this->assertStringContainsString(
            '42602',
            $body,
            "Expected Postgres to reject the payload at the regconfig cast (SQLSTATE 42602).\nResponse body: $body"
        );
    }
}
