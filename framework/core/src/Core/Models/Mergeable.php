<?php namespace Flarum\Core\Models;

use Illuminate\Eloquent\Database\Model;

/**
 * A model that has the ability to be merged into an adjacent model.
 *
 * This is implemented by certain types of posts in a discussion. For example,
 * if a "discussion renamed" post is posted immediately after another
 * "discussion renamed" post, then the new one will be merged into the old one.
 */
interface Mergeable
{
    /**
     * Save the model, given that it is going to appear immediately after the
     * passed model.
     *
     * @param \Illuminate\Eloquent\Database\Model $previous
     * @return Illuminate\Eloquent\Database\Model The model resulting after the
     *     merge. If the merge is unsuccessful, this should be the current model
     *     instance. Otherwise, it should be the model that was merged into.
     */
    public function saveAfter(Model $previous);
}
