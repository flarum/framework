<?php

use Flarum\Database\Migration;
use Flarum\Group\Group;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('flags', function (Blueprint $table) {
            $table->unsignedInteger('post_id')->nullable()->change();
            $table->unsignedInteger('target_user_id')->nullable()->index();
            $table->foreign('target_user_id')->references('id')->on('users')->onDelete('cascade');
        });

        $permissions = Migration::addPermissions([
            'user.flag' => Group::MEMBER_ID,
            'user.viewFlags' => Group::MODERATOR_ID,
        ]);
        $permissions['up']($schema);
    },
    'down' => function (Builder $schema) {
        // Account flags cannot be represented by the old schema.
        $schema->getConnection()->table('flags')->whereNull('post_id')->delete();
        $schema->table('flags', function (Blueprint $table) {
            $table->dropForeign(['target_user_id']);
            $table->dropIndex(['target_user_id']);
            $table->dropColumn('target_user_id');
            $table->unsignedInteger('post_id')->nullable(false)->change();
        });

        $permissions = Migration::addPermissions([
            'user.flag' => Group::MEMBER_ID,
            'user.viewFlags' => Group::MODERATOR_ID,
        ]);
        $permissions['down']($schema);
    },
];
