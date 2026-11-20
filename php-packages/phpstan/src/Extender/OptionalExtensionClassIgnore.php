<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Extender;

use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;

final class OptionalExtensionClassIgnore implements IgnoreErrorExtension
{
    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        return $error->getIdentifier() === 'class.notFound'
            && $node->getAttribute(OptionalExtensionVisitor::UNAVAILABLE_EXTENSION_REFERENCE, false);
    }
}
