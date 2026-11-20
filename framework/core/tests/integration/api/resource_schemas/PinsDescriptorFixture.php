<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\resource_schemas;

/**
 * Keeps the JS test fixture `js/tests/fixtures/descriptors/{type}.json` equal to the live descriptor,
 * so the frontend integration tests run against what the server really sends.
 *
 * Regenerate with `FLARUM_WRITE_DESCRIPTOR_FIXTURES=1` set while running the schema test.
 */
trait PinsDescriptorFixture
{
    /**
     * Fetches `$type`'s descriptor as `$actorId` and checks it against (or writes) the JS fixture.
     * The descriptor is decoded to objects for writing so empty JSON objects stay `{}`.
     */
    protected function assertDescriptorFixture(string $type, int $actorId = 1): void
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/'.$type, ['authenticatedAs' => $actorId])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $attributes = json_decode($body)->data->attributes;
        $descriptor = json_decode($body, true)['data']['attributes'];

        $path = dirname(__DIR__, 4).'/js/tests/fixtures/descriptors/'.$type.'.json';
        $json = json_encode($attributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";

        if (getenv('FLARUM_WRITE_DESCRIPTOR_FIXTURES') === '1') {
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }

            file_put_contents($path, $json);
            $this->addToAssertionCount(1);

            return;
        }

        $this->assertFileExists($path, "Missing JS descriptor fixture for `$type`. Generate it with FLARUM_WRITE_DESCRIPTOR_FIXTURES=1 (see below).");

        $this->assertSame(
            json_decode((string) file_get_contents($path), true),
            $descriptor,
            "The committed JS fixture js/tests/fixtures/descriptors/$type.json no longer matches the live `$type` descriptor.\n"
            ."If the change is intended, regenerate it from framework/core:\n"
            ."  FLARUM_WRITE_DESCRIPTOR_FIXTURES=1 php ../../vendor/bin/phpunit -c tests/phpunit.integration.xml --filter descriptor_matches_the_js_fixture tests/integration/api/resource_schemas\n"
            .'then re-run the JS tests (yarn test tests/integration/admin) and review the diff.'
        );
    }
}
