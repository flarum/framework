<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Work\ComposerStepsFactory;
use Flarum\Foundation\Config;
use PHPUnit\Framework\TestCase;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * The site address must be able to reach the health check.
 *
 * 🚨 This exists because it could not, for the entire life of the feature.
 *
 * `ComposerStepsFactory::__construct` took `?Config $config = null`, and the
 * single line that constructs it — the StepsFactory binding in the service
 * provider — passed only Paths. So `siteUrl()` returned '' on every install,
 * every run logged "No site address is configured, so this update will not be
 * judged by whether the site answers", and the automatic
 * rollback-when-the-site-breaks never ran anywhere.
 *
 * Nothing threw. An optional dependency that nobody supplies is indistinguishable
 * from one that is not needed, which is exactly why this is now required and
 * why that is asserted rather than assumed.
 */
class SiteAddressReachesTheHealthCheckTest extends TestCase
{
    public function test_config_is_a_required_constructor_dependency(): void
    {
        $param = $this->configParameter();

        $this->assertNotNull($param, 'ComposerStepsFactory no longer takes a Config at all.');
        $this->assertFalse(
            $param->isOptional(),
            'Config became optional again. The one caller that builds this must pass it, and a default hides that it does not.'
        );
    }

    public function test_config_is_not_nullable(): void
    {
        $type = $this->configParameter()?->getType();

        $this->assertInstanceOf(ReflectionNamedType::class, $type);
        $this->assertFalse($type->allowsNull(), 'A nullable Config is how the health check silently stopped existing.');
        $this->assertSame(Config::class, $type->getName());
    }

    /**
     * The binding has to hand one over as well — a required parameter the
     * container cannot satisfy is a different silent failure, not a fix.
     */
    public function test_the_service_provider_passes_a_config(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/src/ExtensionManagerServiceProvider.php');

        $this->assertIsString($source);
        $this->assertMatchesRegularExpression(
            '/new ComposerStepsFactory\s*\(.*Config::class.*\)/s',
            $source,
            'The StepsFactory binding does not pass a Config, which is the original bug.'
        );
    }

    private function configParameter(): ?ReflectionParameter
    {
        foreach ((new \ReflectionClass(ComposerStepsFactory::class))->getConstructor()?->getParameters() ?? [] as $p) {
            if ($p->getName() === 'config') {
                return $p;
            }
        }

        return null;
    }
}
