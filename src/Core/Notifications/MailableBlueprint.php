<?php namespace Flarum\Core\Notifications;

interface MailableBlueprint
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
    public function getEmailSubject();
}
