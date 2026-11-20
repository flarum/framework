<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration;

use Flarum\Locale\TranslatorInterface;
use Flarum\Mentions\Notification\GroupMentionedBlueprint;
use Flarum\Mentions\Notification\PostMentionedBlueprint;
use Flarum\Mentions\Notification\UserMentionedBlueprint;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class NotificationTypeNamesTest extends TestCase
{
    public static function mailableBlueprints(): array
    {
        return [
            [GroupMentionedBlueprint::class],
            [PostMentionedBlueprint::class],
            [UserMentionedBlueprint::class],
        ];
    }

    /**
     * @param class-string<BlueprintInterface> $blueprint
     */
    #[Test]
    #[DataProvider('mailableBlueprints')]
    public function the_email_footer_has_a_name_for_the_type(string $blueprint): void
    {
        $this->extension('flarum-mentions');

        $key = 'core.email.notification.types.'.$blueprint::getType();

        $this->assertNotSame($key, $this->app()->getContainer()->make(TranslatorInterface::class)->trans($key));
    }
}
