<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\GitHub;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;

/**
 * @property int $id
 * @property int $discussion_id
 * @property int|null $user_id
 * @property string $type
 * @property string $repo
 * @property int $number
 * @property string $title
 * @property string $state
 * @property int|null $open_issues
 * @property int|null $closed_issues
 * @property Carbon|null $source_updated_at
 * @property Carbon|null $synced_at
 * @property Carbon|null $created_at
 */
class Link extends AbstractModel
{
    protected $table = 'ideation_github_links';

    public $timestamps = false;

    /** Only the identity of a link; everything else is set from GitHub's answer. */
    protected $fillable = ['discussion_id', 'type', 'repo', 'number'];

    protected $casts = [
        'number' => 'integer',
        'open_issues' => 'integer',
        'closed_issues' => 'integer',
        'source_updated_at' => 'datetime',
        'synced_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function reference(): string
    {
        return $this->type === 'milestone'
            ? $this->repo.' · '.$this->title
            : $this->repo.'#'.$this->number;
    }

    public function url(): string
    {
        $path = ['issue' => 'issues', 'pull' => 'pull', 'milestone' => 'milestone'][$this->type] ?? 'issues';

        return 'https://github.com/'.$this->repo.'/'.$path.'/'.$this->number;
    }
}
