<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Symfony\Component\Translation\TranslatorInterface;

interface MailableInterface
{
    /**
     * Get the name of the view to construct a notification email with.
     *
     * @return string
     */
    public function getEmailView();

    /**
     * Get the subject line for a notification email.
     *
     * @return string
     */
    // TODO: This is temporarily commented out to avoid BC breaks between beta 13 and beta 14.
    // It should be uncommented before beta 15.
    // public function getEmailSubject(TranslatorInterface $translator);
}
