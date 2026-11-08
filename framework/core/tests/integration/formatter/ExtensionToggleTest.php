<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\formatter;

use Flarum\Extend;
use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Extension\Extension;
use Flarum\Formatter\Formatter;
use Flarum\Testing\integration\RefreshesFormatterCache;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression tests for https://github.com/flarum/framework/issues/3758.
 *
 * An extension can configure the formatter differently depending on which
 * other extensions are enabled, so the cached formatter has to be rebuilt
 * whenever any extension is toggled.
 */
class ExtensionToggleTest extends TestCase
{
    use RefreshesFormatterCache;

    /**
     * Stands in for "is the other extension enabled?".
     */
    private bool $boldEnabled = false;

    public static function toggleEvents(): array
    {
        return [
            'enabled' => [Enabled::class],
            'disabled' => [Disabled::class],
        ];
    }

    #[Test]
    #[DataProvider('toggleEvents')]
    public function toggling_an_extension_rebuilds_the_formatter(string $event)
    {
        $this->extend((new Extend\Formatter)->configure(function ($config) {
            if ($this->boldEnabled) {
                $config->BBCodes->addFromRepository('B');
            }
        }));

        $container = $this->app()->getContainer();
        $formatter = $container->make(Formatter::class);

        $this->assertEquals('<t>[B]something[/B]</t>', $formatter->parse('[B]something[/B]'));

        $this->boldEnabled = true;

        $container->make(Dispatcher::class)->dispatch(new $event(new Extension(__DIR__, ['name' => 'acme/other'])));

        $this->assertEquals('<b>something</b>', $formatter->render($formatter->parse('[B]something[/B]')));
    }
}
