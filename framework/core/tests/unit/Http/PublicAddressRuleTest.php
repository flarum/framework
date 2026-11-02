<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Http;

use Flarum\Http\OutboundAddressResolver;
use Flarum\Http\Rule\PublicAddress;
use Flarum\Testing\unit\TestCase;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class PublicAddressRuleTest extends TestCase
{
    private function errors(mixed $value): array
    {
        $loader = new ArrayLoader();
        $loader->addMessages('en', 'validation', ['public_address' => 'The :attribute field must point at a public address.']);

        $validator = (new Factory(new Translator($loader, 'en')))->make(
            ['avatar' => $value],
            ['avatar' => [new PublicAddress(new OutboundAddressResolver())]]
        );

        return $validator->errors()->get('avatar');
    }

    public static function rejectedProvider(): array
    {
        return [
            'loopback literal' => ['https://127.0.0.1/a.png'],
            'metadata literal' => ['http://169.254.169.254/latest/meta-data'],
            'unresolvable host' => ['https://i-do-not-exist.invalid/a.png'],
            'no host' => ['image.png'],
            'not a string' => [['https://8.8.8.8/a.png']],
        ];
    }

    #[Test]
    #[DataProvider('rejectedProvider')]
    public function it_rejects_urls_whose_host_is_not_an_allowed_address(mixed $value): void
    {
        $this->assertSame(['The avatar field must point at a public address.'], $this->errors($value));
    }

    #[Test]
    public function it_accepts_a_url_whose_host_is_a_public_or_private_address(): void
    {
        $this->assertSame([], $this->errors('https://8.8.8.8/a.png'));
        $this->assertSame([], $this->errors('https://[2606:4700:4700::1111]/a.png'));
        $this->assertSame([], $this->errors('http://10.0.0.5/a.png'));
    }
}
