<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Api\Describe;

use Flarum\Api\Describe\FieldDescriber;
use Flarum\Foundation\Config;
use Flarum\Locale\TranslatorInterface;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;

class FieldDescriberTest extends TestCase
{
    #[Test]
    public function translation_lookups_are_memoised_per_instance()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->exactly(3))
            ->method('trans')
            ->willReturnCallback(fn (string $key) => $key === 'core.known' ? 'Known' : $key);

        $describer = new FieldDescriber($translator, new Config(['url' => 'http://localhost']), new NullLogger);

        // Existence check + translation for the known key, existence check for the unknown one.
        $this->assertSame('Known', $describer->translate('core.known'));
        $this->assertSame('Known', $describer->translate('core.known'));
        $this->assertNull($describer->translate('core.unknown'));
        $this->assertNull($describer->translate('core.unknown'));
    }
}
