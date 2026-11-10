<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\tests\unit;

use Flarum\Gdpr\FilterAnonymizedRecipients;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Testing\unit\TestCase;
use Flarum\User\User;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;

class FilterAnonymizedRecipientsTest extends TestCase
{
    private function user(bool $anonymized): User
    {
        $user = m::mock(User::class)->makePartial();
        $user->anonymized = $anonymized;

        return $user;
    }

    #[Test]
    public function anonymized_users_are_removed_and_others_kept(): void
    {
        $normal = $this->user(false);
        $anon = $this->user(true);

        $filter = new FilterAnonymizedRecipients();
        $result = $filter(m::mock(BlueprintInterface::class), [$normal, $anon]);

        $this->assertSame([$normal], $result);
    }

    #[Test]
    public function returns_all_when_none_anonymized(): void
    {
        $a = $this->user(false);
        $b = $this->user(false);

        $filter = new FilterAnonymizedRecipients();

        $this->assertSame([$a, $b], $filter(m::mock(BlueprintInterface::class), [$a, $b]));
    }
}
