<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Tests\Fixtures\OptionalExtension;

use Flarum\Database\AbstractModel;
use Flarum\Extend;
use Flarum\Gdpr\Events\MissingInsideInstalledExtension;
use OptionalExtensionFixture\Events\Completed;
use OptionalExtensionFixture\Events\MissingOutsideConditional;
use OptionalExtensionFixture\Events\MissingWhenExtensionDisabled;
use OptionalExtensionFixture\Extender;

class FixtureModel extends AbstractModel
{
}

// Trigger extender discovery before the rest of this file is analysed.
function checkCast(FixtureModel $model): bool
{
    return $model->optional_extension_fixture;
}

return [
    (new Extend\Model(FixtureModel::class))->cast('optional_extension_fixture', 'boolean'),
    (new Extend\Event())->listen(MissingOutsideConditional::class, static function () {}),
    (new Extend\Conditional())
        ->whenExtensionEnabled('fixture-optional-extension', fn () => [
            (new Extend\Event())->listen(Completed::class, static function () {}),
        ])
        ->whenExtensionEnabled(
            extenders: fn () => [
                (new Extend\Event())->listen(Completed::class, static function (Completed $event): void {
                    $event->observe();
                }),
            ],
            extensionId: 'fixture-optional-extension',
        )
        ->whenExtensionEnabled('fixture-optional-extension', function () {
            return [
                (new Extender())->configure(),
                (new Extend\Event())->listen(123, static function () {}),
            ];
        })
        ->whenExtensionDisabled('fixture-optional-extension', fn () => [
            (new Extend\Event())->listen(MissingWhenExtensionDisabled::class, static function () {}),
        ])
        ->whenExtensionEnabled('flarum-gdpr', fn () => [
            (new Extend\Event())->listen(MissingInsideInstalledExtension::class, static function () {}),
        ]),
];
