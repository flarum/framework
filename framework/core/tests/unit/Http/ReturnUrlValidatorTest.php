<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Http;

use Flarum\Foundation\Config;
use Flarum\Http\ReturnUrlValidator;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ReturnUrlValidatorTest extends TestCase
{
    protected function validator(?array $config = null): ReturnUrlValidator
    {
        return new ReturnUrlValidator(new Config(array_merge([
            'url' => 'http://flarum.test'
        ], $config ?? [])));
    }

    #[Test]
    public function allowed_hosts_default_to_the_forum_host()
    {
        $this->assertEquals(['flarum.test'], $this->validator()->allowedHosts());
    }

    #[Test]
    public function allowed_hosts_include_configured_redirect_domains()
    {
        $hosts = $this->validator(['redirectDomains' => ['idp.example', 'app.example']])->allowedHosts();

        $this->assertEquals(['flarum.test', 'idp.example', 'app.example'], $hosts);
    }

    #[Test]
    public function urls_on_the_forum_host_are_accepted()
    {
        $uri = $this->validator()->validate('http://flarum.test/d/1-hello');

        $this->assertNotNull($uri);
        $this->assertEquals('http://flarum.test/d/1-hello', (string) $uri);
    }

    #[Test]
    public function urls_on_a_configured_redirect_domain_are_accepted()
    {
        $uri = $this->validator(['redirectDomains' => ['idp.example']])
            ->validate('https://idp.example/logout?next=1');

        $this->assertNotNull($uri);
        $this->assertEquals('https://idp.example/logout?next=1', (string) $uri);
    }

    #[Test]
    public function urls_on_any_other_host_are_rejected()
    {
        $this->assertNull($this->validator()->validate('https://evil.example/steal'));
    }

    #[Test]
    public function relative_paths_are_rejected_by_validate()
    {
        // They carry no host to check against the allow list, so they belong to
        // validatePath() instead.
        $this->assertNull($this->validator()->validate('/d/1-hello'));
    }

    #[Test]
    public function unparseable_and_empty_urls_are_rejected()
    {
        $this->assertNull($this->validator()->validate('http://'));
        $this->assertNull($this->validator()->validate(''));
        $this->assertNull($this->validator()->validate(null));
    }

    #[Test]
    public function sanitize_falls_back_to_the_forum_url()
    {
        $this->assertEquals(
            'http://flarum.test',
            (string) $this->validator()->sanitize('https://evil.example')
        );
    }

    #[Test]
    public function sanitize_honours_an_explicit_fallback()
    {
        $this->assertEquals(
            'http://flarum.test/base',
            (string) $this->validator()->sanitize('https://evil.example', 'http://flarum.test/base')
        );
    }

    #[Test]
    public function same_origin_paths_are_accepted_by_validate_path()
    {
        $validator = $this->validator();

        $this->assertEquals('/', $validator->validatePath('/'));
        $this->assertEquals('/d/1-hello', $validator->validatePath('/d/1-hello'));
        $this->assertEquals('/d/1?page=2', $validator->validatePath('/d/1?page=2'));
    }

    #[Test]
    public function off_origin_paths_are_rejected_by_validate_path()
    {
        $validator = $this->validator();

        $this->assertEquals('/', $validator->validatePath('//evil.example'));
        $this->assertEquals('/', $validator->validatePath('https://evil.example'));
        $this->assertEquals('/', $validator->validatePath('d/1-hello'));
        $this->assertEquals('/', $validator->validatePath("/d/1\r\nLocation: https://evil.example"));
        $this->assertEquals('/', $validator->validatePath(''));
        $this->assertEquals('/', $validator->validatePath(null));
    }

    #[Test]
    public function validate_path_honours_an_explicit_fallback()
    {
        $this->assertEquals('/all', $this->validator()->validatePath('https://evil.example', '/all'));
    }
}
