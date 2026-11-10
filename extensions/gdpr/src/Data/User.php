<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Data;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class User extends Type
{
    public static function piiFields(): array
    {
        return ['email', 'username', 'last_seen_at', 'joined_at', 'preferences', 'nickname', 'suspend_reason', 'suspend_message'];
    }

    public function export(): ?array
    {
        $remove = ['id', 'password', 'groups', 'anonymized'];

        return ['user.json' => $this->encodeForExport(
            Arr::except($this->user->toArray(), $remove)
        )];
    }

    public function anonymize(): void
    {
        $columns = $this->getTableColumns($this->user);

        // `$remove` here means "skip this column when wiping to null" — i.e. preserve
        // the existing value or set it explicitly below. NOT NULL boolean columns
        // (e.g. has_avatar_*) are listed here because nulling them would violate
        // their constraint; they're set explicitly after the loop instead.
        $remove = ['id', 'username', 'password', 'email', 'is_email_confirmed', 'preferences', 'joined_at', 'anonymized', 'discussion_count', 'comment_count', 'has_avatar_2x', 'has_avatar_3x'];

        foreach ($columns as $column) {
            if (in_array($column, $remove)) {
                continue;
            }

            $this->user->{$column} = null;
        }

        $anonymousName = $this->settings->get('flarum-gdpr.default-anonymous-username');
        $this->user->rename("{$anonymousName}{$this->erasureRequest->id}");
        $this->user->changeEmail("{$this->user->username}@flarum-gdpr.local");
        $this->user->is_email_confirmed = false;
        $this->user->setPasswordAttribute(Str::random(40));
        $this->user->setPreferencesAttribute([]);
        $this->user->joined_at = Carbon::now();
        $this->user->anonymized = true;
        // The avatar files themselves are removed by Data\Assets::anonymize(); reset
        // the variant flags here to match.
        $this->user->has_avatar_2x = false;
        $this->user->has_avatar_3x = false;
        $this->user->groups()->sync([]);

        $this->user->save();
    }

    public function delete(): void
    {
        $this->user->delete();
    }
}
