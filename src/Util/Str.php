<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Util;

class Str
{
    /**
     * Function to create a sanitized slug ready to use.
     *
     * Slug is Unicode compatible but unicode chars are URL encoded by default.
     * The urldecode() function can be used to decode slug when needed.
     *
     * @param string $title The string used to generate the slug (usually post title).
     * @return string The sanitized slug with unicode support.
     */
    public static function slug($title)
    {
        // Replace accents and create sanitized slug
        $slug = (new self)->sanitize_slug_string((new self)->convert_accents($title));

        $slug = urldecode($slug);

        return $slug;
    }

    /**
     * Function to replace some chars with dashes and removing non-friendly-slug chars.
     *
     * The expected result is a slug with alphanumeric charset, underscore (_), dash (-),
     * and keep all unicode characters URL-encoded.
     *
     * @param string $str The string to be sanitized for slug.
     * @return string The sanitized slug with unicode support.
     */
    public function sanitize_slug_string($str)
    {
        $str = preg_replace('/%([a-fA-F0-9][a-fA-F0-9])/', '----$1----', $str);
        // Remove % char that are not part of URLencoded chars.
        $str = str_replace('%', '', $str);
        // Restore original URLencoded chars with %.
        $str = preg_replace('/----([a-fA-F0-9][a-fA-F0-9])----/', '%$1', $str);

        if ($this->seems_utf8($str)) {
            if (function_exists('mb_strtolower')) {
                $str = mb_strtolower($str, 'UTF-8');
            }
            $str = $this->url_encode_unicode_chars($str);
        }

        // Replace nbsp ( ), ndash (–) and mdash (—) with hyphens (-)
        $str = str_replace(['&nbsp;', '&#160;', '%c2%a0', '&ndash;', '&#8211;', '%e2%80%93',
            '&mdash;', '&#8212;', '%e2%80%94'], '-', $str);

        // Remove non-friendly-slug chars
        $str = str_replace([
            '%c2%a1', '%c2%bf', '%c2%ab', '%c2%bb', '%e2%80%b9', '%e2%80%ba', '%e2%80%98',
            '%e2%80%99', '%e2%80%9c', '%e2%80%9d', '%e2%80%9a', '%e2%80%9b', '%e2%80%9e',
            '%e2%80%9f', '%c2%a9', '%c2%ae', '%c2%b0', '%e2%80%a6', '%e2%84%a2', '%c2%b4',
            '%cb%8a', '%cc%81', '%cd%81', '%cc%80', '%cc%84', '%cc%8c',
        ], '', $str);

        $str = strtolower($str);

        // Simple dot replacement
        $str = str_replace('.', '-', $str);

        // Keep only normalized chars (including URL encoded chars)
        $str = preg_replace('/[^%a-z0-9 _-]/', '', $str);

        // Remove nultiple spaces
        $str = preg_replace('/\s+/', '-', $str);

        // Remove multiple dashes
        $str = preg_replace('/-+/', '-', $str);

        //Remove first and/or last dash if exists
        $str = trim($str, '-');

        return $str;
    }

    /**
     * Makes neccesary replacements to convert accent characters into URL friendly ASCII characters.
     *
     * TODO: German - GERMANY (de_DE), Danish - DENMARK (da_DK), Serbian - SERBIA (sr_RS)
     * This languages needs special replacements but will be pending until we have a function
     * to get forum language (locale used with country code maybe).
     *
     * @param string $string Text where accents chars will be converted to ASCII.
     * @return string Parsed string with accents removed.
     */
    public function convert_accents($string)
    {
        if (! preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }

        if ($this->seems_utf8($string)) {
            $char_replacements = [
            // Replacements for Latin-1 Supplement (U+0080 - U+00FF)
            'ª' => 'a', 'º' => 'o', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A',
            'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O',
            'Ö' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y',
            'Þ' => 'TH', 'ß' => 's', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
            'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
            'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'þ' => 'th', 'ÿ' => 'y', 'Ø' => 'O', '×' => 'x',
            // Replacements for Latin Extended-A (U+0100 - U+017F)
            'Ā' => 'A', 'ā' => 'a', 'Ă' => 'A', 'ă' => 'a', 'Ą' => 'A', 'ą' => 'a',
            'Ć' => 'C', 'ć' => 'c', 'Ĉ' => 'C', 'ĉ' => 'c', 'Ċ' => 'C', 'ċ' => 'c',
            'Č' => 'C', 'č' => 'c', 'Ď' => 'D', 'ď' => 'd', 'Đ' => 'D', 'đ' => 'd',
            'Ē' => 'E', 'ē' => 'e', 'Ĕ' => 'E', 'ĕ' => 'e', 'Ė' => 'E', 'ė' => 'e',
            'Ę' => 'E', 'ę' => 'e', 'Ě' => 'E', 'ě' => 'e', 'Ĝ' => 'G', 'ĝ' => 'g',
            'Ğ' => 'G', 'ğ' => 'g', 'Ġ' => 'G', 'ġ' => 'g', 'Ģ' => 'G', 'ģ' => 'g',
            'Ĥ' => 'H', 'ĥ' => 'h', 'Ħ' => 'H', 'ħ' => 'h', 'Ĩ' => 'I', 'ĩ' => 'i',
            'Ī' => 'I', 'ī' => 'i', 'Ĭ' => 'I', 'ĭ' => 'i', 'Į' => 'I', 'į' => 'i',
            'İ' => 'I', 'ı' => 'i', 'Ĳ' => 'IJ', 'ĳ' => 'ij', 'Ĵ' => 'J', 'ĵ' => 'j',
            'Ķ' => 'K', 'ķ' => 'k', 'ĸ' => 'k', 'Ĺ' => 'L', 'ĺ' => 'l', 'Ļ' => 'L',
            'ļ' => 'l', 'Ľ' => 'L', 'ľ' => 'l', 'Ŀ' => 'L', 'ŀ' => 'l', 'Ł' => 'L',
            'ł' => 'l', 'Ń' => 'N', 'ń' => 'n', 'Ņ' => 'N', 'ņ' => 'n', 'Ň' => 'N',
            'ň' => 'n', 'ŉ' => 'n', 'Ŋ' => 'N', 'ŋ' => 'n', 'Ō' => 'O', 'ō' => 'o',
            'Ŏ' => 'O', 'ŏ' => 'o', 'Ő' => 'O', 'ő' => 'o', 'Œ' => 'OE', 'œ' => 'oe',
            'Ŕ' => 'R', 'ŕ' => 'r', 'Ŗ' => 'R', 'ŗ' => 'r', 'Ř' => 'R', 'ř' => 'r',
            'Ś' => 'S', 'ś' => 's', 'Ŝ' => 'S', 'ŝ' => 's', 'Ş' => 'S', 'ş' => 's',
            'Š' => 'S', 'š' => 's', 'Ţ' => 'T', 'ţ' => 't', 'Ť' => 'T', 'ť' => 't',
            'Ŧ' => 'T', 'ŧ' => 't', 'Ũ' => 'U', 'ũ' => 'u', 'Ū' => 'U', 'ū' => 'u',
            'Ŭ' => 'U', 'ŭ' => 'u', 'Ů' => 'U', 'ů' => 'u', 'Ű' => 'U', 'ű' => 'u',
            'Ų' => 'U', 'ų' => 'u', 'Ŵ' => 'W', 'ŵ' => 'w', 'Ŷ' => 'Y', 'ŷ' => 'y',
            'Ÿ' => 'Y', 'Ź' => 'Z', 'ź' => 'z', 'Ż' => 'Z', 'ż' => 'z', 'Ž' => 'Z',
            'ž' => 'z', 'ſ' => 's',
            // Replacements for Latin Extended-B (U+0180 - U+024F)
            'Ș' => 'S', 'ș' => 's', 'Ț' => 'T', 'ț' => 't',
            // Currencies
            '€' => 'E', '£' => '',
            // Replacements for vietnamese vowels with diacritic
            'Ơ' => 'O', 'ơ' => 'o', 'Ư' => 'U', 'ư' => 'u', 'Ầ' => 'A', 'ầ' => 'a',
            'Ằ' => 'A', 'ằ' => 'a', 'Ề' => 'E', 'ề' => 'e', 'Ồ' => 'O', 'ồ' => 'o',
            'Ờ' => 'O', 'ờ' => 'o', 'Ừ' => 'U', 'ừ' => 'u', 'Ỳ' => 'Y', 'ỳ' => 'y',
            'Ả' => 'A', 'ả' => 'a', 'Ẩ' => 'A', 'ẩ' => 'a', 'Ẳ' => 'A', 'ẳ' => 'a',
            'Ẻ' => 'E', 'ẻ' => 'e', 'Ể' => 'E', 'ể' => 'e', 'Ỉ' => 'I', 'ỉ' => 'i',
            'Ỏ' => 'O', 'ỏ' => 'o', 'Ổ' => 'O', 'ổ' => 'o', 'Ở' => 'O', 'ở' => 'o',
            'Ủ' => 'U', 'ủ' => 'u', 'Ử' => 'U', 'ử' => 'u', 'Ỷ' => 'Y', 'ỷ' => 'y',
            'Ẫ' => 'A', 'ẫ' => 'a', 'Ẵ' => 'A', 'ẵ' => 'a', 'Ẽ' => 'E', 'ẽ' => 'e',
            'Ễ' => 'E', 'ễ' => 'e', 'Ỗ' => 'O', 'ỗ' => 'o', 'Ỡ' => 'O', 'ỡ' => 'o',
            'Ữ' => 'U', 'ữ' => 'u', 'Ỹ' => 'Y', 'ỹ' => 'y', 'Ấ' => 'A', 'ấ' => 'a',
            'Ắ' => 'A', 'ắ' => 'a', 'Ế' => 'E', 'ế' => 'e', 'Ố' => 'O', 'ố' => 'o',
            'Ớ' => 'O', 'ớ' => 'o', 'Ứ' => 'U', 'ứ' => 'u', 'Ạ' => 'A', 'ạ' => 'a',
            'Ậ' => 'A', 'ậ' => 'a', 'Ặ' => 'A', 'ặ' => 'a', 'Ẹ' => 'E', 'ẹ' => 'e',
            'Ệ' => 'E', 'ệ' => 'e', 'Ị' => 'I', 'ị' => 'i', 'Ọ' => 'O', 'ọ' => 'o',
            'Ộ' => 'O', 'ộ' => 'o', 'Ợ' => 'O', 'ợ' => 'o', 'Ụ' => 'U', 'ụ' => 'u',
            'Ự' => 'U', 'ự' => 'u', 'Ỵ' => 'Y', 'ỵ' => 'y',
            // Replacements for Chinese vowels with diacritic (Pinyin)
            'ɑ' => 'a', 'Ǖ' => 'U', 'ǖ' => 'u', 'Ǘ' => 'U', 'ǘ' => 'u',
            'Ǎ' => 'A', 'ǎ' => 'a', 'Ǐ' => 'I', 'ǐ' => 'i', 'Ǒ' => 'O',
            'ǒ' => 'o', 'Ǔ' => 'U', 'ǔ' => 'u', 'Ǚ' => 'U', 'ǚ' => 'u',
            'Ǜ' => 'U', 'ǜ' => 'u',
            ];

            $string = strtr($string, $char_replacements);
        } else {
            // Using ISO-8859-1 when encoding not UTF-8
            $char_replacements = [
                "\x80" => 'E', "\x83" => 'f', "\x8a" => 'S', "\x8e" => 'Z', "\x9a" => 's', "\x9e" => 'z',
                "\x9f" => 'Y', "\xa2" => 'c', "\xa5" => 'Y', "\xb5" => 'u', "\xc0" => 'A', "\xc1" => 'A',
                "\xc2" => 'A', "\xc3" => 'A', "\xc4" => 'A', "\xc5" => 'A', "\xc7" => 'C', "\xc8" => 'E',
                "\xc9" => 'E', "\xca" => 'E', "\xcb" => 'E', "\xcc" => 'I', "\xcd" => 'I', "\xce" => 'I',
                "\xcf" => 'I', "\xd1" => 'N', "\xd2" => 'O', "\xd3" => 'O', "\xd4" => 'O', "\xd5" => 'O',
                "\xd6" => 'O', "\xd8" => 'O', "\xd9" => 'U', "\xda" => 'U', "\xdb" => 'U', "\xdc" => 'U',
                "\xdd" => 'Y', "\xe0" => 'a', "\xe1" => 'a', "\xe2" => 'a', "\xe3" => 'a', "\xe4" => 'a',
                "\xe5" => 'a', "\xe7" => 'c', "\xe8" => 'e', "\xe9" => 'e', "\xea" => 'e', "\xeb" => 'e',
                "\xec" => 'i', "\xed" => 'i', "\xee" => 'i', "\xef" => 'i', "\xf1" => 'n', "\xf2" => 'o',
                "\xf3" => 'o', "\xf4" => 'o', "\xf5" => 'o', "\xf6" => 'o', "\xf8" => 'o', "\xf9" => 'u',
                "\xfa" => 'u', "\xfb" => 'u', "\xfc" => 'u', "\xfd" => 'y', "\xff" => 'y',
                // Double chars
                "\x8c" => 'OE', "\x9c" => 'oe', "\xc6" => 'AE', "\xd0" => 'DH', "\xde" => 'TH', "\xdf" => 'ss',
                "\xe6" => 'ae', "\xf0" => 'dh', "\xfe" => 'th',
            ];

            $string = strtr($string, $char_replacements);
        }

        return $string;
    }

    /**
     * Function to URL-encode unicode values of a given string and use them
     * as part of the URI.
     *
     * @param string $utf8_string
     * @return string String with unicode values properly encoded for URI.
     */
    public function url_encode_unicode_chars($utf8_string)
    {
        $encoded_str = '';
        $values = [];
        $octets = 1;

        $utf8_string_length = mb_strlen($utf8_string, 'ISO-8859-1'); //Length binary-safe

        for ($i = 0; $i < $utf8_string_length; $i++) {
            $value = ord($utf8_string[$i]);

            if ($value < 128) { //The first 128 characters (ASCII) need one byte (one octet)
                $encoded_str .= chr($value);
            } else { //The rest of characters needs more bytes (2, 3 or 4 octets)
                if (count($values) == 0) {
                    if ($value < 224) {
                        $octets = 2;
                    } elseif ($value < 240) {
                        $octets = 3;
                    } else {
                        $octets = 4;
                    }
                }

                $values[] = $value;

                if (count($values) == $octets) {
                    for ($j = 0; $j < $octets; $j++) {
                        $encoded_str .= '%'.dechex($values[$j]);
                    }

                    //Reset
                    $values = [];
                    $octets = 1;
                }
            }
        }

        return $encoded_str;
    }

    /**
     * Checks if a string is utf8 encoded.
     * We use strict UTF-8 check with mbstring extension: mb_detect_encoding($str,'UTF-8', true).
     *
     *
     * @param string $str String to be checked against UTF-8 models
     * @return bool True if string follows a UTF-8 model, false otherwise.
     */
    public function seems_utf8($str)
    {
        if (mb_detect_encoding($str, 'UTF-8', true)) {
            return true;
        } else {
            return false;
        }
    }
}
