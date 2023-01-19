<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Symfony\Contracts\Translation\TranslatorInterface;

interface MailableInterface
{
    /**
     * Get the name of the view to construct a notification email with.
     *
     * @return string|array
     */
    public function getEmailView();

    /**
     * Get the subject line for a notification email.
     *
     * @param TranslatorInterface $translator
     *
     * @return string
     */
    public function getEmailSubject(TranslatorInterface $translator);
}
