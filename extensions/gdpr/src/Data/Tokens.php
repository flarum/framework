<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Data;

use Flarum\Api\ApiKey;
use Flarum\Http\AccessToken;
use Flarum\User\EmailToken;
use Flarum\User\LoginProvider;
use Flarum\User\PasswordToken;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Tokens extends Type
{
    protected array $classes = [
        ApiKey::class,
        AccessToken::class,
        EmailToken::class,
        LoginProvider::class,
        PasswordToken::class,
    ];

    public function export(): ?array
    {
        $exportData = [];

        foreach ($this->classes as $class) {
            $baseName = Str::afterLast($class, '\\');

            $class::query()
                ->where('user_id', $this->user->id)
                ->each(function ($token) use ($baseName, &$exportData) {
                    $id = $token->getKey();

                    $exportData[] = ["tokens/token-$baseName-$id.json" => json_encode(Arr::except($token->toArray(), ['user_id', 'token', 'key']), JSON_PRETTY_PRINT)];
                });
        }

        return $exportData;
    }

    public static function anonymizeDescription(): string
    {
        return self::deleteDescription();
    }

    public function anonymize(): void
    {
        $this->delete();
    }

    public function delete(): void
    {
        foreach ($this->classes as $class) {
            $class::query()->where(
                'user_id',
                $this->user->id
            )->delete();
        }
    }
}
