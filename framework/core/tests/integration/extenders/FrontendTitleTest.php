<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Discussion\Discussion;
use Flarum\Extend\Frontend;
use Flarum\Frontend\Document;
use Flarum\Frontend\Driver\TitleDriverInterface;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

class FrontendTitleTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Test Discussion', 'user_id' => 1, 'first_post_id' => 1]
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>can i haz potat?</p></t>'],
            ],
        ]);

        $this->setting('forum_title', 'Flarum');
    }

    #[Test]
    public function basic_title_driver_is_used_by_default()
    {
        $this->assertTitleEquals('Test Discussion - Flarum');
    }

    #[Test]
    public function custom_title_driver_works_if_set()
    {
        $this->extend((new Frontend('forum'))->title(CustomTitleDriver::class));

        $this->assertTitleEquals('CustomTitle');
    }

    private function assertTitleEquals(string $title): void
    {
        $response = $this->send($this->request('GET', '/d/1'));

        preg_match('/\<title\>(?<title>[^<]+)\<\/title\>/m', $body = $response->getBody()->getContents(), $matches);

        $this->assertEquals($title, $matches['title'] ?? null, $body);
    }
}

class CustomTitleDriver implements TitleDriverInterface
{
    public function makeTitle(Document $document, ServerRequestInterface $request, array $forumApiDocument): string
    {
        return 'CustomTitle';
    }
}
