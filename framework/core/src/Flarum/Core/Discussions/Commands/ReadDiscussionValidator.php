<?php namespace Flarum\Core\Posts\Commands;

use Flarum\Core\Support\CommandValidator;
use Flarum\Core\Support\Exceptions\PermissionDeniedException;

class ReadDiscussionValidator extends CommandValidator
{
    public function validate($command)
    {
        // The user must be logged in (not a guest) to have state data about
        // a discussion. Thus, we deny permission to mark a discussion as read
        // if the user doesn't exist in the database.
        if (! $command->user->exists) {
            throw new PermissionDeniedException;
        }
        
        parent::validate($command);
    }
}
