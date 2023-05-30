<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Flarum\Locale\TranslatorInterface;

interface MailableInterface
{
    /**
     * Get the name of the view to construct a notification email with.
     */
    public function getEmailView(): string|array;

    /**
     * Get the subject line for a notification email.
     */
    public function getEmailSubject(TranslatorInterface $translator): string;
}
