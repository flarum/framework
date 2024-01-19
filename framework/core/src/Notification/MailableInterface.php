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
     * Get the names of the views to construct a notification email with.
     *
     * To provide the best experience for the user, Flarum expects both a `text` and `html` view.
     *
     * @return array{
     *     text: string,
     *     html: string
     * }
     */
    public function getEmailViews(): array;

    /**
     * Get the subject line for a notification email.
     */
    public function getEmailSubject(TranslatorInterface $translator): string;
}
