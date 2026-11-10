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
 * @property int         $id
 * @property int         $user_id
 * @property int         $actor_id
 * @property string|null $file
 * @property Carbon      $created_at
 * @property Carbon      $destroys_at
 * @property Carbon|null $downloaded_at
 * @property string|null $downloaded_ip
 * @property string|null $downloaded_user_agent
 * @property User        $user
 */
class Export extends AbstractModel
{
    protected $table = 'gdpr_exports';

    protected $casts = [
        'created_at' => 'datetime',
        'destroys_at' => 'datetime',
        'downloaded_at' => 'datetime',
    ];

    public static function byFile(?string $file): ?self
    {
        if ($file === null || $file === '') {
            return null;
        }

        return self::query()
            ->whereNotNull('file')
            ->where('file', $file)
            ->where('destroys_at', '>', Carbon::now())
            ->whereNull('downloaded_at')
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Exports whose stored ZIP should be cleaned up. Includes already-downloaded
     * exports (artifact no longer needed) and those past their expiry window.
     *
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public static function destroyable(): \Illuminate\Database\Eloquent\Builder
    {
        return self::query()
            ->whereNotNull('file')
            ->where(function ($query) {
                $query
                    ->where('destroys_at', '<=', Carbon::now())
                    ->orWhereNotNull('downloaded_at');
            });
    }
}
