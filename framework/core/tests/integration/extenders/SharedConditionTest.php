<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extension\Condition;
use Flarum\Extend;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Container\Container;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class SharedConditionTest extends TestCase
{
    #[Test]
    public function a_reusable_extension_condition_controls_extenders_and_can_be_reevaluated(): void
    {
        $condition = Condition::extensionEnabled('flarum-tags');
        $this->extension('flarum-tags');
        $this->extend((new Extend\Conditional())->when($condition, fn () => [
            new class implements Extend\ExtenderInterface {
                public function extend(Container $container, ?\Flarum\Extension\Extension $extension = null): void
                {
                    $container->make(SettingsRepositoryInterface::class)->set('shared_condition_applied', 'yes');
                }
            }
        ]));

        $container = $this->app()->getContainer();

        $this->assertSame('yes', $container->make(SettingsRepositoryInterface::class)->get('shared_condition_applied'));
        $this->assertTrue($container->call($condition));
        $this->assertFalse($container->call(Condition::extensionEnabled('acme-missing')));
    }

    public static function conditions(): iterable
    {
        yield 'enabled' => ['extensionEnabled', ['flarum-tags'], true];
        yield 'disabled' => ['extensionDisabled', ['flarum-tags'], false];
        yield 'missing is disabled' => ['extensionDisabled', ['acme-missing'], true];
        yield 'all enabled' => ['extensionsEnabled', [['flarum-tags', 'acme-missing']], false];
        yield 'all disabled' => ['extensionsDisabled', [['acme-first', 'acme-second']], true];
        yield 'one still enabled' => ['extensionsDisabled', [['acme-missing', 'flarum-tags']], false];
        yield 'loose setting' => ['setting', ['condition_value', 1], true];
        yield 'strict setting' => ['setting', ['condition_value', 1, true], false];
        yield 'all' => ['all', [], true];
        yield 'any' => ['any', [], true];
        yield 'not' => ['not', [], false];
    }

    #[Test]
    #[DataProvider('conditions')]
    public function conditions_share_extension_and_setting_semantics_with_extenders(string $method, array $arguments, bool $expected): void
    {
        $this->extension('flarum-tags');
        $container = $this->app()->getContainer();
        $container->make(SettingsRepositoryInterface::class)->set('condition_value', '1');

        $condition = match ($method) {
            'all' => Condition::all(Condition::extensionEnabled('flarum-tags'), Condition::setting('condition_value', '1')),
            'any' => Condition::any(Condition::extensionEnabled('acme-missing'), Condition::setting('condition_value', '1')),
            'not' => Condition::not(Condition::extensionEnabled('flarum-tags')),
            default => Condition::$method(...$arguments),
        };

        $this->assertSame($expected, $container->call($condition));
    }

    #[Test]
    public function the_standard_test_harness_can_prepare_dependent_extensions(): void
    {
        $this->extension('flarum-tags', 'flarum-mentions');

        $container = $this->app()->getContainer();

        $this->assertTrue($container->make(\Flarum\Extension\ExtensionManager::class)->isEnabled('flarum-mentions'));
    }
}
