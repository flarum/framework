<?php

namespace Flarum\Messages\Dialog\Event;

use Flarum\Messages\UserDialogState;

class UserRead
{
    public function __construct(
        public UserDialogState $state
    ) {
    }
}
