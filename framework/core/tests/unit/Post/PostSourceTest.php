<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Post;

use Flarum\Post\PostSource;
use Flarum\Testing\unit\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class PostSourceTest extends TestCase
{
    #[Test]
    #[DataProvider('validKeys')]
    public function accepts_lowercase_keys(string $key): void
    {
        $this->assertSame($key, (new PostSource($key))->key);
    }

    public static function validKeys(): array
    {
        return [['api'], ['mcp'], ['reply-by-email'], ['app_2'], ['0']];
    }

    #[Test]
    #[DataProvider('invalidKeys')]
    public function rejects_keys_search_could_not_match(string $key): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PostSource($key);
    }

    public static function invalidKeys(): array
    {
        return [
            'empty' => [''],
            'uppercase' => ['API'],
            'trailing newline' => ["api\n"],
            'space' => ['my app'],
            'comma' => ['api,mcp'],
            'too long' => [str_repeat('a', PostSource::MAX_KEY_LENGTH + 1)],
        ];
    }

    #[Test]
    public function stored_name_is_trimmed_cut_and_null_when_blank(): void
    {
        $this->assertNull((new PostSource('api', '  '))->storedName());
        $this->assertSame('Team Assistant', (new PostSource('mcp', ' Team Assistant '))->storedName());
        $this->assertSame(PostSource::MAX_NAME_LENGTH, mb_strlen((string) (new PostSource('mcp', str_repeat('é', 150)))->storedName()));
    }
}
