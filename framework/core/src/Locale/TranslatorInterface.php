<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Illuminate\Contracts\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface as SymfonyTranslatorInterface;

interface TranslatorInterface extends Translator, SymfonyTranslatorInterface
{
    public function getLocale(): string;
}
