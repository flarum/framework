<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Models;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int    $id
 * @property int    $user_id
 * @property int    $actor_id
 * @property string $file
 * @property Carbon $created_at
 * @property Carbon $destroys_at
 * @property User   $user
 */
class Export extends AbstractModel
{
    protected $table = 'gdpr_exports';

    protected $casts = [
        'created_at'  => 'datetime',
        'destroys_at' => 'datetime',
    ];

    public static function byFile(string $file): ?self
    {
        return self::query()
            ->where('file', $file)
            ->first();
    }

    public static function exported(User $user, string $tmp, User $actor): self
    {
        return tap(new self(), function ($export) use ($user, $tmp, $actor) {
            $export->user_id = $user->id;
            $export->actor_id = $actor->id;
            $export->file = $tmp;
            $export->created_at = Carbon::now();
            $export->destroys_at = Carbon::now()->addDay();
            $export->save();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function destroyable()
    {
        return self::query()
            ->where('destroys_at', '<=', Carbon::now());
    }
}
