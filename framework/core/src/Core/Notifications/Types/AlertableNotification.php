<?php namespace Flarum\Core\Notifications\Types;

interface AlertableNotification
{
    public function getAlertData();

    public static function getType();
}
