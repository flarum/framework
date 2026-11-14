<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\tests\integration\api;

use Flarum\Foundation\Paths;
use Flarum\Gdpr\Data\Type;
use Flarum\Gdpr\Extend\UserData;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Filesystem\Factory;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * An export is put together in storage/tmp. Nothing it leaves there is cleaned
 * up later, and the files are named after the user, so it must not leave any.
 */
class ExportTemporaryFilesTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-gdpr');

        $this->setting('mail_driver', 'log');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            'gdpr_exports' => [],
        ]);
    }

    public function tearDown(): void
    {
        $disk = $this->app()->getContainer()->make(Factory::class)->disk('gdpr-export');
        $disk->delete($disk->allFiles());

        parent::tearDown();
    }

    protected function temporaryFiles(): array
    {
        return glob($this->app()->getContainer()->make(Paths::class)->storage.'/tmp/data-export-*') ?: [];
    }

    protected function requestExport(): ResponseInterface
    {
        return $this->send(
            $this->request('POST', '/api/gdpr-exports', [
                'authenticatedAs' => 2,
                'json' => ['data' => ['attributes' => ['userId' => 2]]],
            ])->withAttribute('bypassCsrfToken', true)
        );
    }

    #[Test]
    public function a_finished_export_leaves_no_temporary_files()
    {
        $before = $this->temporaryFiles();

        $this->assertEquals(201, $this->requestExport()->getStatusCode());

        $this->assertSame([], array_values(array_diff($this->temporaryFiles(), $before)));
    }

    #[Test]
    public function an_export_that_fails_leaves_no_temporary_files()
    {
        $this->extend((new UserData())->addType(FailingDataType::class));

        $before = $this->temporaryFiles();

        $this->assertEquals(500, $this->requestExport()->getStatusCode());

        $this->assertSame([], array_values(array_diff($this->temporaryFiles(), $before)));
    }
}

class FailingDataType extends Type
{
    public static function exportDescription(): string
    {
        return '';
    }

    public function export(): ?array
    {
        throw new RuntimeException('This data could not be exported.');
    }

    public static function anonymizeDescription(): string
    {
        return '';
    }

    public function anonymize(): void
    {
    }

    public static function deleteDescription(): string
    {
        return '';
    }

    public function delete(): void
    {
    }
}
