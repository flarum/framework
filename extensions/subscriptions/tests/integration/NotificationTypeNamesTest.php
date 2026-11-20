<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Tests\integration;

use Flarum\Locale\TranslatorInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Subscriptions\Notification\NewPostBlueprint;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class NotificationTypeNamesTest extends TestCase
{
    public static function mailableBlueprints(): array
    {
        return [
            [NewPostBlueprint::class],
        ];
    }

    /**
     * @param class-string<BlueprintInterface> $blueprint
     */
    #[Test]
    #[DataProvider('mailableBlueprints')]
    public function the_email_footer_has_a_name_for_the_type(string $blueprint): void
    {
        $this->extension('flarum-subscriptions');

        $key = 'core.email.notification.types.'.$blueprint::getType();

        $this->assertNotSame($key, $this->app()->getContainer()->make(TranslatorInterface::class)->trans($key));
    }
}
