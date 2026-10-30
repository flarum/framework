<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Flarum\Tags\TagRepository;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

/**
 * Regression tests for https://github.com/flarum/framework/issues/5252.
 *
 * flarum/tags is optional, so parsing content must not require its
 * repository to be constructed when the tags extension is not enabled.
 */
class MentionsWithoutTagsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'potato', 'email' => 'potato@machine.local', 'is_email_confirmed' => 1],
            ],
        ]);
    }

    #[Test]
    public function parsing_does_not_resolve_the_tag_repository_when_tags_is_disabled()
    {
        // Stands in for flarum/tags not being installed: building it fails.
        $this->app()->getContainer()->bind(TagRepository::class, function () {
            throw new RuntimeException('TagRepository should not be resolved.');
        });

        $xml = $this->app()->getContainer()->make('flarum.formatter')->parse('@"potato"#3 hello');

        $this->assertStringContainsString('USERMENTION', $xml);
    }
}
