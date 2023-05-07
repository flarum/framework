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
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;

class Resolver
{
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
                    foreach ($expression->items as $item) {
                        if ($item->value instanceof MethodCall) {
                            // Conditional extenders
                            if ($item->value->name->toString() === 'whenExtensionEnabled') {
                                $conditionalExtenders = $item->value->args[1] ?? null;

                                if ($conditionalExtenders->value instanceof Array_) {
                                    foreach ($conditionalExtenders->value->items as $conditionalExtender) {
                                        if ($conditionalExtender->value instanceof MethodCall) {
                                            $extenders[] = $this->resolveExtender($conditionalExtender->value);
                                        }
                                    }
                                }
                            }
                            // Normal extenders
                            else {
                                $extenders[] = $this->resolveExtender($item->value);
                            }
                        }
                    }
                }
            }
        }

        return $extenders;
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
            throw new \Exception('Unable to resolve extender for '.get_class($value->var));
        }

        return $this->resolveExtenderNew($value->var, $methodStack);
    }
}
