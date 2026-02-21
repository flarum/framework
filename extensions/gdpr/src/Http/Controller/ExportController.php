<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Http\Controller;

use Flarum\Gdpr\Models\Export;
use Flarum\Gdpr\StorageManager;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ExportController implements RequestHandlerInterface
{
    public function __construct(protected StorageManager $storageManager)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $file = Arr::get($request->getQueryParams(), 'file');

        $export = Export::byFile($file);

        if ($export) {
            return new Response(
                $this->storageManager->getStoredExport($export),
                200,
                [
                    'Content-Type' => 'application/zip',
                    'Content-Length' => (string) $this->storageManager->getStoredExportSize($export),
                    'Content-Disposition' => 'attachment; filename="data-export-'.$export->user->username.'-'.$export->created_at->toIso8601String().'.zip"',
                ]
            );
        }

        throw new FileNotFoundException();
    }
}
