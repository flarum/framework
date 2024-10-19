<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages;

use Flarum\Database\AbstractModel;
use Flarum\Database\Eloquent\Collection;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Formatter\Formattable;
use Flarum\Formatter\HasFormattedContent;
use Flarum\Foundation\EventGeneratorTrait;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $dialog_id
 * @property int|null $user_id
 * @property string $content
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Dialog $dialog
 * @property-read User|null $user
 * @property-read Collection<int, User> $mentionsUsers
 * @property-read Collection<int, Post> $mentionsPosts
 * @property-read Collection<int, Group> $mentionsGroups
 * @property-read Collection<int, Tag> $mentionsTags
 */
class DialogMessage extends AbstractModel implements Formattable
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;
    use HasFormattedContent;

    protected $table = 'dialog_messages';

    public $timestamps = true;

    protected $guarded = [];

    public function dialog(): BelongsTo
    {
        return $this->belongsTo(Dialog::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentionsUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'dialog_message_mentions_user', 'dialog_message_id', 'mentions_user_id');
    }

    public function mentionsPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'dialog_message_mentions_post', 'dialog_message_id', 'mentions_post_id');
    }

    public function mentionsGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'dialog_message_mentions_group', 'dialog_message_id', 'mentions_group_id');
    }

    public function mentionsTags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'dialog_message_mentions_tag', 'dialog_message_id', 'mentions_tag_id');
    }
}
