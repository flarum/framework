<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\user;

use Flarum\Foundation\ValidationException;
use Flarum\Testing\integration\TestCase;
use Flarum\User\AvatarValidator;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\Attributes\Test;

class AvatarImageValidationTest extends TestCase
{
    private function validator(): AvatarValidator
    {
        return $this->app()->getContainer()->make(AvatarValidator::class);
    }

    private function uploadedFile(string $path, string $mediaType, string $filename): UploadedFile
    {
        return new UploadedFile($path, filesize($path) ?: 0, UPLOAD_ERR_OK, $filename, $mediaType);
    }

    #[Test]
    public function accepts_a_normal_image(): void
    {
        $file = $this->uploadedFile(__DIR__.'/../../fixtures/assets/avatar.png', 'image/png', 'avatar.png');

        $this->validator()->assertImageValid('avatar', $file);

        // No exception thrown means the image validated successfully.
        $this->addToAssertionCount(1);
    }

    /**
     * A tiny PNG that declares enormous dimensions in its header ("decompression
     * bomb") must be rejected *before* it is decoded, so it can never force a
     * huge memory allocation.
     *
     * @see https://cwe.mitre.org/data/definitions/409.html
     */
    #[Test]
    public function rejects_a_decompression_bomb(): void
    {
        // 33-byte PNG declaring 30000x30000 (900 megapixels). getimagesize reads
        // the dimensions from the IHDR header without allocating the pixel buffer.
        $ihdr = 'IHDR'.pack('N', 30000).pack('N', 30000)."\x08\x02\x00\x00\x00";
        $png = "\x89PNG\r\n\x1a\n".pack('N', 13).$ihdr.pack('N', crc32($ihdr));

        $path = tempnam(sys_get_temp_dir(), 'bomb').'.png';
        file_put_contents($path, $png);

        try {
            $file = $this->uploadedFile($path, 'image/png', 'bomb.png');

            $this->expectException(ValidationException::class);

            $this->validator()->assertImageValid('avatar', $file);
        } finally {
            @unlink($path);
        }
    }
}
