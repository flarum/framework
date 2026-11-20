<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Extender;

use Flarum\Extend\Conditional;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;
use PhpParser\NodeVisitorAbstract;

final class OptionalExtensionVisitor extends NodeVisitorAbstract
{
    public const UNAVAILABLE_EXTENSION_REFERENCE = 'flarumUnavailableExtensionReference';

    public function __construct(private InstalledExtensions $installedExtensions)
    {
    }

    public function leaveNode(Node $node): null
    {
        if ($node instanceof MethodCall && $node->name instanceof Identifier && $node->name->toLowerString() === 'whenextensionenabled' && ! $node->isFirstClassCallable()) {
            $this->markCallbackReferences($node);
        }

        return null;
    }

    private function markCallbackReferences(MethodCall $call): void
    {
        $receiver = $call->var;
        while ($receiver instanceof MethodCall) {
            $receiver = $receiver->var;
        }

        if (! $receiver instanceof New_ || ! $receiver->class instanceof Name || strcasecmp($receiver->class->toString(), Conditional::class) !== 0) {
            return;
        }

        $arguments = [];
        foreach ($call->getArgs() as $index => $arg) {
            if ($arg->unpack) {
                return;
            }

            $arguments[$arg->name?->toString() ?? $index] = $arg->value;
        }

        $extensionId = $arguments['extensionId'] ?? $arguments[0] ?? null;
        $callback = $arguments['extenders'] ?? $arguments[1] ?? null;

        if ($extensionId instanceof String_ && ($callback instanceof ArrowFunction || $callback instanceof Closure) && ! $this->installedExtensions->has($extensionId->value)) {
            foreach ((new NodeFinder())->findInstanceOf($callback, Node::class) as $reference) {
                $reference->setAttribute(self::UNAVAILABLE_EXTENSION_REFERENCE, true);
            }
        }
    }
}
