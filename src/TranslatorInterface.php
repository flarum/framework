<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Symfony\Component\Translation;

/**
 * @deprecated beta 16, remove beta 17.
 * This is here to provide a graceful transition for classes typehinting the old interface.
 * Temporarily, `Flarum\Locale\Translator` will implement this to avoid breaking that typehint.
 * Before beta 17, this should be removed from autoload.
 */
interface TranslatorInterface
{
}
