<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\unit;

use Flarum\OAuthProvider\ClientRegistration\ClientMetadata;
use Flarum\OAuthProvider\ClientRegistration\ClientMetadataException;
use Flarum\OAuthProvider\ClientRegistration\MetadataDocumentClients;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ClientMetadataTest extends TestCase
{
    #[Test]
    public function redirect_uris_must_be_https_or_loopback_http(): void
    {
        $this->assertTrue(ClientMetadata::isAllowedRedirectUri('https://app.example.com/cb'));
        $this->assertTrue(ClientMetadata::isAllowedRedirectUri('http://127.0.0.1:33418/'));
        $this->assertTrue(ClientMetadata::isAllowedRedirectUri('http://localhost/cb'));
        $this->assertFalse(ClientMetadata::isAllowedRedirectUri('http://app.example.com/cb'));
        $this->assertFalse(ClientMetadata::isAllowedRedirectUri('myapp://cb'));
        $this->assertFalse(ClientMetadata::isAllowedRedirectUri('https://app.example.com/cb#x'));
        $this->assertFalse(ClientMetadata::isAllowedRedirectUri('https://user@app.example.com/cb'));
    }

    #[Test]
    public function redirect_uris_must_be_a_non_empty_list(): void
    {
        $this->expectException(ClientMetadataException::class);

        ClientMetadata::redirectUris([]);
    }

    #[Test]
    public function optional_urls_are_dropped_unless_https(): void
    {
        $this->assertSame('https://app.example.com/logo.png', ClientMetadata::optionalUrl('https://app.example.com/logo.png'));
        $this->assertNull(ClientMetadata::optionalUrl('http://app.example.com/logo.png'));
        $this->assertNull(ClientMetadata::optionalUrl('javascript:alert(1)'));
        $this->assertNull(ClientMetadata::optionalUrl(['https://app.example.com']));
    }

    #[Test]
    public function metadata_document_urls_need_https_and_a_path(): void
    {
        $this->assertTrue(MetadataDocumentClients::isMetadataDocumentUrl('https://app.example.com/oauth/client.json'));
        $this->assertFalse(MetadataDocumentClients::isMetadataDocumentUrl('https://app.example.com'));
        $this->assertFalse(MetadataDocumentClients::isMetadataDocumentUrl('https://app.example.com/'));
        $this->assertFalse(MetadataDocumentClients::isMetadataDocumentUrl('http://app.example.com/client.json'));
        $this->assertFalse(MetadataDocumentClients::isMetadataDocumentUrl('https://app.example.com/client.json#x'));
        $this->assertFalse(MetadataDocumentClients::isMetadataDocumentUrl('https://app.example.com/'.str_repeat('a', 300)));
        $this->assertFalse(MetadataDocumentClients::isMetadataDocumentUrl('abc123'));
    }

    #[Test]
    public function at_most_ten_redirect_uris_of_2000_characters_are_accepted(): void
    {
        $this->assertCount(10, ClientMetadata::redirectUris(array_map(fn ($i) => "https://app.example.com/cb$i", range(1, 10))));
        $this->assertTrue(ClientMetadata::isAllowedRedirectUri('https://app.example.com/'.str_repeat('a', 2000 - 24)));
        $this->assertFalse(ClientMetadata::isAllowedRedirectUri('https://app.example.com/'.str_repeat('a', 2001 - 24)));

        $this->expectException(ClientMetadataException::class);

        ClientMetadata::redirectUris(array_map(fn ($i) => "https://app.example.com/cb$i", range(1, 11)));
    }

    #[Test]
    public function names_lose_control_and_bidi_characters(): void
    {
        // U+202E RIGHT-TO-LEFT OVERRIDE, U+2066 LEFT-TO-RIGHT ISOLATE, U+2069 POP DIRECTIONAL ISOLATE.
        $this->assertSame('Good Appgnp.exe', ClientMetadata::name("Good App\u{202E}gnp.exe"));
        $this->assertSame('ab', ClientMetadata::name("\u{2066}a\u{2069}\u{202A}\u{202C}b"));
        $this->assertSame('Tab Name', ClientMetadata::name("Tab\t Name\n\x00"));
        $this->assertSame('Ünïcödé 名前', ClientMetadata::name(' Ünïcödé 名前 '));
        $this->assertNull(ClientMetadata::name("\u{202E}\u{200B}"));
        $this->assertNull(ClientMetadata::name("\xff\xfe"));
    }
}
