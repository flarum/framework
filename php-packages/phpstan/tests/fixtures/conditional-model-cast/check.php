<?php

use Flarum\User\User;

function setConditionalCasts(User $user): void
{
    $user->enabled_extension_cast = true;
    $user->disabled_extension_cast = true;
    $user->setting_cast = true;
    $user->generic_condition_cast = true;
}
