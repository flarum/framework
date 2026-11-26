<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Tests\unit;

use Flarum\Ideation\GitHub\Reference;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ReferenceTest extends TestCase
{
    #[Test]
    public function references_are_parsed_strictly()
    {
        $ok = [
            'https://github.com/flarum/framework/pull/5034' => ['flarum/framework', 'pull', 5034],
            'https://github.com/flarum/framework/issues/4000#issuecomment-1' => ['flarum/framework', 'issue', 4000],
            'https://github.com/FoF/Upload/milestone/3' => ['fof/upload', 'milestone', 3],
            'flarum/framework#12' => ['flarum/framework', 'issue', 12],
        ];

        foreach ($ok as $input => [$repo, $type, $number]) {
            $ref = Reference::parse($input);
            $this->assertSame([$repo, $type, $number], [$ref?->repo, $ref?->type, $ref?->number], $input);
        }

        foreach (['https://evil.example/flarum/framework/pull/1', 'https://github.com/flarum/../pull/1', 'flarum/framework#0', 'javascript:alert(1)', 'https://github.com/flarum/framework/pull/1@evil.example', 'https://github.com.evil.example/a/b/pull/1'] as $bad) {
            $this->assertNull(Reference::parse($bad), $bad);
        }
    }
}
