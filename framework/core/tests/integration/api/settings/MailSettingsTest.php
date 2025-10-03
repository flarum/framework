<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\settings;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class MailSettingsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * @test
     */
    public function smtpDriverWithWhitespaceIsInvalidated()
    {
        $this->setting('mail_driver', 'smtp');
        $this->setting('mail_host', ' world');
        $this->setting('mail_port', ' 587 ');
        $this->setting('mail_encryption', 'tls');
        $this->setting('mail_username', 'user ');
        $this->setting('mail_password', ' password');

        $mailSettingsResponse = $this->send(
            $this->request('GET', '/api/mail/settings', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $mailSettingsResponse->getStatusCode());
        
        $data = json_decode((string) $mailSettingsResponse->getBody(), true);

        $this->assertFalse($data['data']['attributes']['sending']);

        $this->assertArrayHasKey('errors', $data['data']['attributes']);

        $this->assertArrayHasKey('mail_host', $data['data']['attributes']['errors']);
        $this->assertEquals('The mail host must not contain leading or trailing whitespace.', $data['data']['attributes']['errors']['mail_host'][0]);

        $this->assertArrayHasKey('mail_port', $data['data']['attributes']['errors']);
        $this->assertEquals('The mail port must not contain leading or trailing whitespace.', $data['data']['attributes']['errors']['mail_port'][0]);

        $this->assertArrayHasKey('mail_username', $data['data']['attributes']['errors']);
        $this->assertEquals('The mail username must not contain leading or trailing whitespace.', $data['data']['attributes']['errors']['mail_username'][0]);

        $this->assertArrayHasKey('mail_password', $data['data']['attributes']['errors']);
        $this->assertEquals('The mail password must not contain leading or trailing whitespace.', $data['data']['attributes']['errors']['mail_password'][0]);
    }

    /**
     * @test
     */
    public function smtpDriverWithValidSettingsIsNotInvalidated()
    {
        $this->setting('mail_driver', 'smtp');
        $this->setting('mail_host', 'mail.example.com');
        $this->setting('mail_port', '587');
        $this->setting('mail_encryption', 'tls');
        $this->setting('mail_username', 'user');
        $this->setting('mail_password', 'password');

        $mailSettingsResponse = $this->send(
            $this->request('GET', '/api/mail/settings', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $mailSettingsResponse->getStatusCode());
        
        $data = json_decode((string) $mailSettingsResponse->getBody(), true);

        $this->assertEmpty($data['data']['attributes']['errors']);
        $this->assertTrue($data['data']['attributes']['sending']);
    }

    /**
     * @test
     */
    public function mailgunDriverWithWhitespaceIsInvalidated()
    {
        $this->setting('mail_driver', 'mailgun');
        $this->setting('mail_mailgun_secret', 'key ');
        $this->setting('mail_mailgun_domain', ' example.com');
        $this->setting('mail_mailgun_region', 'api.mailgun.net');

        $mailSettingsResponse = $this->send(
            $this->request('GET', '/api/mail/settings', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $mailSettingsResponse->getStatusCode());

        $data = json_decode((string) $mailSettingsResponse->getBody(), true);

        $this->assertFalse($data['data']['attributes']['sending']);

        $this->assertArrayHasKey('errors', $data['data']['attributes']);

        $this->assertArrayHasKey('mail_mailgun_secret', $data['data']['attributes']['errors']);
        $this->assertEquals('The mail mailgun secret must not contain leading or trailing whitespace.', $data['data']['attributes']['errors']['mail_mailgun_secret'][0]);

        $this->assertArrayHasKey('mail_mailgun_domain', $data['data']['attributes']['errors']);
        $this->assertEquals('The mail mailgun domain format is invalid.', $data['data']['attributes']['errors']['mail_mailgun_domain'][0]);
    }

    /**
     * @test
     */
    public function mailgunDriverWithValidSettingsIsNotInvalidated()
    {
        $this->setting('mail_driver', 'mailgun');
        $this->setting('mail_mailgun_secret', 'key');
        $this->setting('mail_mailgun_domain', 'example.com');
        $this->setting('mail_mailgun_region', 'api.mailgun.net');

        $mailSettingsResponse = $this->send(
            $this->request('GET', '/api/mail/settings', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $mailSettingsResponse->getStatusCode());

        $data = json_decode((string) $mailSettingsResponse->getBody(), true);

        $this->assertEmpty($data['data']['attributes']['errors']);
        $this->assertTrue($data['data']['attributes']['sending']);
    }
}
