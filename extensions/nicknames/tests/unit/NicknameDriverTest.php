<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames\Tests\unit;

use Flarum\Nicknames\NicknameDriver;
use Flarum\Testing\unit\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class NicknameDriverTest extends TestCase
{
    private function displayName(?string $nickname, string $username = 'username'): string
    {
        $driver = new NicknameDriver();

        $user = new User();
        $user->nickname = $nickname;
        $user->username = $username;

        return $driver->displayName($user);
    }

    #[Test]
    public function clean_nickname_is_unchanged(): void
    {
        $this->assertSame('Jane Smith', $this->displayName('Jane Smith'));
    }

    #[Test]
    public function falls_back_to_username_when_no_nickname(): void
    {
        $this->assertSame('alice', $this->displayName(null, 'alice'));
    }

    #[Test]
    public function dot_gets_zero_width_space_inserted(): void
    {
        $this->assertSame("nasty.\u{200B}com", $this->displayName('nasty.com'));
    }

    #[Test]
    public function multiple_dots_all_get_zero_width_space(): void
    {
        $this->assertSame("first.\u{200B}last.\u{200B}com", $this->displayName('first.last.com'));
    }

    #[Test]
    public function square_brackets_are_stripped(): void
    {
        $this->assertSame('CLICK', $this->displayName('[CLICK]'));
    }

    #[Test]
    public function parentheses_are_stripped(): void
    {
        $this->assertSame('evilcom', $this->displayName('evil(com)'));
    }

    #[Test]
    public function angle_brackets_are_stripped(): void
    {
        $this->assertSame("evil.\u{200B}com", $this->displayName('<evil.com>'));
    }

    #[Test]
    public function markdown_link_syntax_is_neutralised(): void
    {
        $this->assertSame("CLICKhttps://evil.\u{200B}com", $this->displayName('[CLICK](https://evil.com)'));
    }

    #[Test]
    public function username_fallback_also_gets_sanitized(): void
    {
        $this->assertSame("nasty.\u{200B}com", $this->displayName(null, 'nasty.com'));
    }

    #[Test]
    #[DataProvider('safeNicknames')]
    public function safe_nicknames_are_preserved(string $nickname, string $expected): void
    {
        $this->assertSame($expected, $this->displayName($nickname));
    }

    public static function safeNicknames(): array
    {
        return [
            'plain name' => ['Alice',          'Alice'],
            'name with space' => ['Jane Smith',      'Jane Smith'],
            'name with hyphen' => ['Anne-Marie',      'Anne-Marie'],
            'name with numbers' => ['user42',          'user42'],
            'unicode name' => ['Ánna',            'Ánna'],
        ];
    }
}
