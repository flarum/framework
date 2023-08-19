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
     * 
     * To provide the best experince for the user, provide both a `text` and `html` view.
     * 
     * Example:
     * ```php
     * return [
     *  'text' => 'flarum-subscriptions::emails.plain.newPost', 
     *  'html' => 'flarum-subscriptions::emails.html.newPost'
     * ];
     * ```
     */
    public function getEmailView(): array;

    /**
     * Get the subject line for a notification email.
     */
    public function getEmailSubject(TranslatorInterface $translator): string;

    /**
     * Get the serialized type of this activity.
     */
    public static function getType(): string;
}
