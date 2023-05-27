<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Illuminate\Contracts\Translation\Translator;

/**
 * @mixin \Symfony\Contracts\Translation\TranslatorInterface
 */
interface TranslatorInterface extends Translator
{
}
