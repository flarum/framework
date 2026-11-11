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
        $columns = $this->user->getConnection()->getSchemaBuilder()->getColumns($this->user->getTable());

        // `$remove` here means "skip this column when wiping to null" — i.e. preserve
        // the existing value or set it explicitly below.
        $remove = ['id', 'username', 'password', 'email', 'is_email_confirmed', 'preferences', 'joined_at', 'anonymized', 'discussion_count', 'comment_count'];

        foreach ($columns as $column) {
            if (in_array($column['name'], $remove)) {
                continue;
            }

            // Only wipe columns that can hold null. NOT NULL columns (such as the
            // has_avatar_* flags below, or columns other extensions add to `users`)
            // would violate their constraint on PostgreSQL and SQLite, and MySQL
            // would silently coerce the null instead. Their defaults are reported as
            // raw, driver-specific SQL, so they can't be reliably reset to them either;
            // they keep their current value. Extensions that store personal data in
            // such a column should anonymize it with their own data type.
            if (! $column['nullable']) {
                continue;
            }

            $this->user->{$column['name']} = null;
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
