<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Testing;

use Flarum\Gdpr\Contracts\DataType;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\User\User;
use InvalidArgumentException;

/** @phpstan-require-extends \Flarum\Testing\integration\TestCase */
trait InteractsWithGdpr
{
    /**
     * The constructor (or container binding) must accept user and erasureRequest,
     * as Flarum\Gdpr\Data\Type does.
     *
     * @template T of DataType
     * @param class-string<T> $class
     * @return T
     */
    protected function gdprDataType(string $class, int $userId, ?ErasureRequest $request = null): DataType
    {
        if (! is_a($class, DataType::class, true)) {
            throw new InvalidArgumentException('The GDPR data type must implement '.DataType::class.'.');
        }

        $container = $this->app()->getContainer();

        return $container->make($class, [
            'user' => User::findOrFail($userId),
            'erasureRequest' => $request,
        ]);
    }
}
