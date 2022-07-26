<?php

namespace Flarum\Frontend\Asset;

use Flarum\Frontend\Compiler\JsCompiler;

class Js extends Type
{
    protected string $compilerClass = JsCompiler::class;
}
