<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;

/**
 * @property int $discussion_id
 * @property int $user_id
 * @property Carbon|null $created_at
 */
class Vote extends AbstractModel
{
    protected $table = 'ideation_votes';

    public $timestamps = false;

    public $incrementing = false;

    protected $casts = ['created_at' => 'datetime'];
}
