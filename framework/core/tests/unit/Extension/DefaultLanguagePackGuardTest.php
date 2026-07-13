<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Extension;

use Flarum\Extension\DefaultLanguagePackGuard;
use Flarum\Extension\Event\Disabling;
use Flarum\Extension\Extension;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Flarum\User\Exception\PermissionDeniedException;
use PHPUnit\Framework\Attributes\Test;

class DefaultLanguagePackGuardTest extends TestCase
{
    private function guard(string $defaultLocale): DefaultLanguagePackGuard
    {
        $settings = $this->createStub(SettingsRepositoryInterface::class);
        $settings->method('get')
            ->willReturnCallback(function ($key, $default = null) use ($defaultLocale) {
                return $key === 'default_locale' ? $defaultLocale : $default;
            });

        return new DefaultLanguagePackGuard($settings);
    }

    private function disabling(array $extra, string $name = 'flarum/lang-english'): Disabling
    {
        return new Disabling(new Extension('/tmp/ext', [
            'name' => $name,
            'extra' => $extra,
        ]));
    }

    #[Test]
    public function it_blocks_disabling_the_default_language_pack()
    {
        $this->expectException(PermissionDeniedException::class);

        $this->guard('en')->handle(
            $this->disabling(['flarum-locale' => ['code' => 'en', 'title' => 'English']])
        );
    }

    #[Test]
    public function it_allows_disabling_a_non_default_language_pack()
    {
        $this->expectNotToPerformAssertions();

        $this->guard('en')->handle(
            $this->disabling(['flarum-locale' => ['code' => 'fr', 'title' => 'French']])
        );
    }

    #[Test]
    public function it_allows_disabling_a_non_language_pack_extension()
    {
        $this->expectNotToPerformAssertions();

        $this->guard('en')->handle(
            $this->disabling(['flarum-extension' => ['title' => 'Some Extension']], 'acme/some-extension')
        );
    }
}
