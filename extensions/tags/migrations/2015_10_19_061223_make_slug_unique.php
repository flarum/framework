<?php

namespace Flarum\Tags\Migration;

use Flarum\Database\AbstractMigration;
use Illuminate\Database\Schema\Blueprint;

class MakeSlugUnique extends AbstractMigration
{
    public function up()
    {
        $this->schema->table('tags', function (Blueprint $table) {
            $table->string('slug', 100)->change();
            $table->unique('slug');
        });
    }

    public function down()
    {
        $this->schema->table('tags', function (Blueprint $table) {
            $table->string('slug', 255)->change();
            $table->dropUnique('tags_slug_unique');
        });
    }
}
