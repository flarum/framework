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
}
