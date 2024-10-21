<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend\Frontend;
use Flarum\Frontend\Document;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FrontendContentTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    #[Test]
    public function content_added_with_low_priority_by_default()
    {
        $title = null;

        $this->extend(
            (new Frontend('forum'))
                ->content(function (Document $document) use (&$title) {
                    $title = $document->title;
                }),
        );

        $body = $this->send(
            $this->request('GET', '/')
        )->getBody()->getContents();

        $this->assertNotNull($title, $body);
    }

    #[Test]
    public function content_can_be_added_with_high_priority()
    {
        $title = 1;

        $this->extend(
            (new Frontend('forum'))
                ->content(function (Document $document) use (&$title) {
                    $title = $document->title;
                }, 110),
        );

        $body = $this->send(
            $this->request('GET', '/')
        )->getBody()->getContents();

        $this->assertNull($title, $body);
    }

    #[Test]
    public function contents_can_be_added_with_different_priorities()
    {
        $test = [];

        $this->extend(
            (new Frontend('forum'))
                ->content(function (Document $document) use (&$test) {
                    $test[] = 1;
                }, 110)
                ->content(function (Document $document) use (&$test) {
                    $test[] = 3;
                }, 90)
                ->content(function (Document $document) use (&$test) {
                    $test[] = 2;
                }, 100),
        );

        $body = $this->send(
            $this->request('GET', '/')
        )->getBody()->getContents();

        $this->assertEquals([1, 2, 3], $test, $body);
    }
}
