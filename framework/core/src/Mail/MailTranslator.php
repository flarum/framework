<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Locale\TranslatorInterface;

/**
 * The translator email views are given.
 *
 * Behaves like the ordinary translator except that parameter values are
 * replaced by markers instead of being substituted directly, so that they are
 * not present when {@see MailFormatter} renders the result. The markers are
 * swapped back, escaped, once rendering is done.
 *
 * Email templates therefore need no changes — including those in extensions
 * that will never be updated — while a discussion title or display name can no
 * longer inject markup into a notification.
 */
class MailTranslator implements TranslatorInterface
{
    public function __construct(
        protected TranslatorInterface $translator
    ) {
    }

    public function trans($id, array $parameters = [], $domain = null, $locale = null): string
    {
        return $this->translator->trans($id, SafeSubstitution::mark($parameters), $domain, $locale);
    }

    public function get($key, array $replace = [], $locale = null): string
    {
        return $this->translator->get($key, SafeSubstitution::mark($replace), $locale);
    }

    public function choice($key, $number, array $replace = [], $locale = null): string
    {
        return $this->translator->choice($key, $number, SafeSubstitution::mark($replace), $locale);
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    public function setLocale($locale): void
    {
        $this->translator->setLocale($locale);
    }
}
