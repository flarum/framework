<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Image;

/**
 * Packs PNG images into an .ico file. Neither GD nor Intervention can write
 * one, and every browser in use reads PNG-compressed icon entries.
 */
class IcoEncoder
{
    /**
     * @param array<int, string> $pngs PNG data, keyed by the square size it was drawn at
     */
    public static function encode(array $pngs): string
    {
        ksort($pngs);

        $header = pack('vvv', 0, 1, count($pngs));
        $directory = '';
        $data = '';
        $offset = 6 + 16 * count($pngs);

        foreach ($pngs as $size => $png) {
            $dimension = $size >= 256 ? 0 : $size;

            $directory .= pack('CCCCvvVV', $dimension, $dimension, 0, 0, 1, 32, strlen($png), $offset + strlen($data));
            $data .= $png;
        }

        return $header.$directory.$data;
    }
}
