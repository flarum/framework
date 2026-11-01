<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\protocol\Fixtures;

use Flarum\Mcp\Overview\ExtensionOwnership;
use Flarum\Mcp\Tests\integration\protocol\RequestCostTest;

class CountingOwnership extends ExtensionOwnership
{
    protected function buildNamespaces(): array
    {
        RequestCostTest::$calls[] = 'namespaces';

        return parent::buildNamespaces();
    }
}
