<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Extender;

use Flarum\PHPStan\Extender\MethodCall as ExtenderMethodCall;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;

class Resolver
{
    private const CONDITIONAL_EXTENDER_ARGUMENTS = [
        'when' => 1,
        'whenExtensionDisabled' => 1,
        'whenExtensionEnabled' => 1,
        'whenSetting' => 2,
    ];

    /** @var Extender[] */
    private $cachedExtenders = [];
    /** @var FilesProvider */
    private $extenderFilesProvider;
    /** @var Parser */
    private $parser;

    public function __construct(FilesProvider $extenderFilesProvider, Parser $parser)
    {
        $this->extenderFilesProvider = $extenderFilesProvider;
        $this->parser = $parser;
    }

    public function getExtenders(): array
    {
        if ($this->cachedExtenders) {
            return $this->cachedExtenders;
        }

        return $this->cachedExtenders = $this->resolveExtenders();
    }

    public function getExtendersFor(string $extenderClass, ...$args): array
    {
        $extenders = [];

        foreach ($this->getExtenders() as $extender) {
            if ($extender->isExtender($extenderClass)) {
                $extenders[] = $extender;
            }
        }

        return $extenders;
    }

    private function resolveExtenders(): array
    {
        $extenders = [];

        foreach ($this->extenderFilesProvider->getExtenderFiles() as $extenderFile) {
            $extenders = array_merge($extenders, $this->resolveExtendersFromFile($extenderFile));
        }

        return $extenders;
    }

    /**
     * Retrieves all extenders from a given `extend.php` file.
     *
     * @return Extender[]
     * @throws ParserErrorsException
     * @throws \Exception
     */
    private function resolveExtendersFromFile($extenderFile): array
    {
        /** @var Extender[] $extenders */
        $extenders = [];

        $statements = $this->parser->parseFile($extenderFile);

        if ($statements[0] instanceof Namespace_) {
            $statements = $statements[0]->stmts;
        }

        foreach ($statements as $statement) {
            if ($statement instanceof Return_) {
                $expression = $statement->expr;

                if ($expression instanceof Array_) {
                    $extenders = array_merge($extenders, $this->resolveExtendersFromArray($expression));
                }
            }
        }

        return $extenders;
    }

    /**
     * @return Extender[]
     */
    private function resolveExtendersFromArray(Array_ $array): array
    {
        $extenders = [];

        foreach ($array->items as $item) {
            if (! $item?->value instanceof MethodCall) {
                continue;
            }

            $extender = $this->resolveExtender($item->value);

            if ($extender->isExtender('Conditional')) {
                $extenders = array_merge($extenders, $this->resolveConditionalExtenders($item->value));
            } else {
                $extenders[] = $extender;
            }
        }

        return $extenders;
    }

    /**
     * @return Extender[]
     */
    private function resolveConditionalExtenders(MethodCall $methodCall): array
    {
        $extenders = [];

        do {
            $methodName = $methodCall->name->toString();
            $argumentIndex = self::CONDITIONAL_EXTENDER_ARGUMENTS[$methodName] ?? null;

            if ($argumentIndex !== null) {
                $expression = $methodCall->args[$argumentIndex]->value ?? null;
                $array = $this->resolveConditionalExtenderArray($expression);

                if ($array !== null) {
                    $extenders = array_merge($extenders, $this->resolveExtendersFromArray($array));
                }
            }

            $methodCall = $methodCall->var instanceof MethodCall ? $methodCall->var : null;
        } while ($methodCall !== null);

        return $extenders;
    }

    private function resolveConditionalExtenderArray(?Expr $expression): ?Array_
    {
        if ($expression instanceof Array_) {
            return $expression;
        }

        if ($expression instanceof ArrowFunction) {
            return $expression->expr instanceof Array_ ? $expression->expr : null;
        }

        if ($expression instanceof Closure) {
            foreach ($expression->stmts as $statement) {
                if ($statement instanceof Return_ && $statement->expr instanceof Array_) {
                    return $statement->expr;
                }
            }
        }

        return null;
    }

    private function resolveExtenderNew(New_ $var, array $methodCalls = []): Extender
    {
        return new Extender($var->class->toString(), array_map(function (Arg $arg) {
            $arg->value->setAttributes([]);

            return $arg->value;
        }, $var->args), $methodCalls);
    }

    private function resolveMethod(MethodCall $var): ExtenderMethodCall
    {
        return new ExtenderMethodCall($var->name->toString(), array_map(function (Arg $arg) {
            $arg->value->setAttributes([]);

            return $arg->value;
        }, $var->args));
    }

    private function resolveExtender(MethodCall $value): Extender
    {
        $methodStack = [$this->resolveMethod($value)];

        while ($value->var instanceof MethodCall) {
            $methodStack[] = $this->resolveMethod($value->var);
            $value = $value->var;
        }

        $methodStack = array_reverse($methodStack);

        if (! $value->var instanceof New_) {
            throw new \Exception('Unable to resolve extender for '.$value->var::class);
        }

        return $this->resolveExtenderNew($value->var, $methodStack);
    }
}
