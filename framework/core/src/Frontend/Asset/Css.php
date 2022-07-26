<?php

namespace Flarum\Frontend\Asset;

use Flarum\Frontend\Compiler\LessCompiler;

class Css extends Type
{
    protected string $compilerClass = LessCompiler::class;
}
