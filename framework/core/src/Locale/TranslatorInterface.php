<?php

namespace Flarum\Locale;

use Illuminate\Contracts\Translation\Translator;

/**
 * @mixin \Symfony\Contracts\Translation\TranslatorInterface
 */
interface TranslatorInterface extends Translator
{
}
