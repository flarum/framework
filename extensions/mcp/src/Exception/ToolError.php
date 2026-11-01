<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Exception;

use RuntimeException;

/**
 * A failure the model can act on. From a tool it becomes a result with
 * `isError: true`; from a resource or prompt it becomes a JSON-RPC error.
 * The message is shown to the model verbatim, so it must not leak anything
 * the actor may not see.
 */
class ToolError extends RuntimeException
{
}
