<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Log\Context;

use Flarum\Log\Context\Repository;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RepositoryTest extends TestCase
{
    #[Test]
    public function get_hidden_returns_stored_value()
    {
        $repository = new Repository();
        $repository->addHidden('foo', 'bar');

        $this->assertSame('bar', $repository->getHidden('foo'));
    }

    #[Test]
    public function get_hidden_returns_default_when_key_missing()
    {
        $repository = new Repository();

        $this->assertNull($repository->getHidden('missing'));
        $this->assertSame('fallback', $repository->getHidden('missing', 'fallback'));
    }

    #[Test]
    public function add_get_forget_hidden_round_trip()
    {
        $repository = new Repository();
        $repository->addHidden(['a' => 1, 'b' => 2]);

        $this->assertSame(1, $repository->getHidden('a'));
        $this->assertSame(['a' => 1, 'b' => 2], $repository->allHidden());

        $repository->forgetHidden('a');

        $this->assertNull($repository->getHidden('a'));
        $this->assertSame(['b' => 2], $repository->allHidden());
    }

    #[Test]
    public function hidden_data_is_isolated_from_public_data()
    {
        $repository = new Repository();
        $repository->add('foo', 'public');
        $repository->addHidden('foo', 'hidden');

        $this->assertSame('public', $repository->get('foo'));
        $this->assertSame('hidden', $repository->getHidden('foo'));
    }
}
