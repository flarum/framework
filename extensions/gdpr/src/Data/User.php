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

        $remove = ['id', 'username', 'password', 'email', 'is_email_confirmed', 'preferences', 'joined_at', 'anonymized', 'discussion_count', 'comment_count'];

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
        $this->user->groups()->sync([]);

        $this->user->save();
    }

    public function delete(): void
    {
        $this->user->delete();
    }
}
