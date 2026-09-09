<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Rules;

use Flarum\Extend\ApiController;
use Flarum\Extend\ApiSerializer;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

/**
 * @implements Rule<MethodCall>
 */
class ExtenderCallbackSignatureRule implements Rule
{
    private const EXTENDED = '<extended>';

    private const MODEL = 1;

    /**
     * @var array<class-string, array<string, array{int, array<string, string|null>}>>
     */
    private const SIGNATURES = [
        ApiSerializer::class => [
            'attributes' => [0, ['serializer' => self::EXTENDED, 'model' => null, 'attributes' => 'array']],
            'attribute' => [1, ['serializer' => self::EXTENDED, 'model' => null, 'attributes' => 'array']],
            'relationship' => [1, ['serializer' => self::EXTENDED, 'model' => null]],
        ],
        ApiController::class => [
            'prepareDataQuery' => [0, ['controller' => self::EXTENDED]],
            'prepareDataForSerialization' => [0, ['controller' => self::EXTENDED, 'data' => null, 'request' => ServerRequestInterface::class, 'document' => Document::class]],
        ],
    ];

    private const MERGE_FUNCTIONS = ['array_merge', 'array_replace', 'array_merge_recursive'];

    /** @var ReflectionProvider */
    private $reflectionProvider;

    /** @var Parser */
    private $parser;

    /** @var NodeFinder */
    private $nodeFinder;

    public function __construct(ReflectionProvider $reflectionProvider, Parser $parser)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->parser = $parser;
        $this->nodeFinder = new NodeFinder();
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Identifier) {
            return [];
        }

        $method = $node->name->toString();
        $extender = $this->extenderClass($scope->getType($node->var));

        if (! isset(self::SIGNATURES[$extender][$method])) {
            return [];
        }

        [$argIndex, $signature] = self::SIGNATURES[$extender][$method];
        $arg = $node->getArgs()[$argIndex] ?? null;
        $callback = $arg ? $this->callback($arg->value, $scope) : null;

        if (! $callback) {
            return [];
        }

        $messages = [];
        $names = array_keys($signature);

        foreach ($callback->getParams() as $position => $param) {
            $name = $param->var->name;
            $expected = array_search($name, $names, true);

            if ($expected !== false && $expected !== $position) {
                $messages[] = sprintf('declares $%s as parameter #%d, but it is called as ($%s).', $name, $position + 1, implode(', $', $names));
            }

            $passed = $this->passedType($signature[$names[$position] ?? ''] ?? null, $node, $scope);

            if ($param->type && $passed) {
                $declared = $scope->getFunctionType($param->type, false, false);

                if (! $declared->isSuperTypeOf($passed)->yes()) {
                    $messages[] = sprintf('declares parameter #%d $%s as %s, but it receives %s.', $position + 1, $name, $declared->describe(VerbosityLevel::typeOnly()), $passed->describe(VerbosityLevel::typeOnly()));
                }
            }
        }

        $model = $callback->getParams()[self::MODEL] ?? null;

        if ($model && isset($names[self::MODEL]) && $this->returns($callback, $model->var->name)) {
            $messages[] = sprintf('returns the model ($%s), which exposes all of its raw attributes.', $model->var->name);
        }

        return array_map(function (string $message) use ($extender, $method, $arg): RuleError {
            return RuleErrorBuilder::message("$extender::$method() callback $message")->line($arg->getLine())->build();
        }, $messages);
    }

    private function extenderClass(Type $type): ?string
    {
        foreach (array_keys(self::SIGNATURES) as $class) {
            if ((new ObjectType($class))->isSuperTypeOf($type)->yes()) {
                return $class;
            }
        }

        return null;
    }

    private function passedType(?string $type, MethodCall $node, Scope $scope): ?Type
    {
        if ($type === null) {
            return null;
        }

        if ($type === 'array') {
            return new ArrayType(new MixedType(), new MixedType());
        }

        if ($type !== self::EXTENDED) {
            return new ObjectType($type);
        }

        $var = $node->var;

        while ($var instanceof MethodCall) {
            $var = $var->var;
        }

        if (! $var instanceof Expr\New_ || ! isset($var->getArgs()[0])) {
            return null;
        }

        $class = $scope->getType($var->getArgs()[0]->value);

        return $class instanceof ConstantStringType ? new ObjectType($class->getValue()) : null;
    }

    private function callback(Expr $expr, Scope $scope): ?FunctionLike
    {
        if ($expr instanceof FunctionLike) {
            return $expr;
        }

        $type = $scope->getType($expr);

        if ($type instanceof ConstantStringType) {
            return $this->classMethod($type->getValue(), '__invoke');
        }

        if ($type instanceof ConstantArrayType && count($type->getValueTypes()) === 2) {
            [$class, $method] = $type->getValueTypes();

            if ($class instanceof ConstantStringType && $method instanceof ConstantStringType) {
                return $this->classMethod($class->getValue(), $method->getValue());
            }
        }

        return null;
    }

    private function classMethod(string $class, string $method): ?FunctionLike
    {
        if (! $this->reflectionProvider->hasClass($class)) {
            return null;
        }

        $file = $this->reflectionProvider->getClass($class)->getFileName();

        if (! $file) {
            return null;
        }

        foreach ($this->nodeFinder->findInstanceOf($this->parser->parseFile($file), Class_::class) as $node) {
            if ($node->namespacedName && $node->namespacedName->toString() === $class) {
                return $node->getMethod($method);
            }
        }

        return null;
    }

    private function returns(FunctionLike $callback, string $variable): bool
    {
        if ($callback instanceof Expr\ArrowFunction) {
            return $this->yields($callback->expr, $variable);
        }

        foreach ($this->nodeFinder->findInstanceOf($callback->getStmts() ?? [], Return_::class) as $return) {
            if ($return->expr && $this->yields($return->expr, $variable)) {
                return true;
            }
        }

        return false;
    }

    private function yields(Expr $expr, string $variable): bool
    {
        if ($expr instanceof Expr\Variable) {
            return $expr->name === $variable;
        }

        foreach ($this->operands($expr) as $operand) {
            if ($this->yields($operand, $variable)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Expr[]
     */
    private function operands(Expr $expr): array
    {
        if ($expr instanceof Expr\Array_) {
            return array_map(function ($item) {
                return $item->value;
            }, array_filter($expr->items));
        }

        if ($expr instanceof Expr\FuncCall && $expr->name instanceof Node\Name && in_array($expr->name->toLowerString(), self::MERGE_FUNCTIONS, true)) {
            return array_map(function ($arg) {
                return $arg->value;
            }, $expr->getArgs());
        }

        if ($expr instanceof Expr\BinaryOp\Plus || $expr instanceof Expr\BinaryOp\Coalesce) {
            return [$expr->left, $expr->right];
        }

        if ($expr instanceof Expr\Ternary) {
            return [$expr->if ?? $expr->cond, $expr->else];
        }

        if ($expr instanceof Expr\Cast\Array_ || $expr instanceof Expr\Assign) {
            return [$expr->expr];
        }

        return [];
    }
}
