<?php
namespace Flarum\Migrations\Core;
use Illuminate\Database\Schema\Blueprint;
use Flarum\Database\AbstractMigration;

class AddIpToPosts extends AbstractMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->table('posts', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->table('posts', function (Blueprint $table) {
            $table->dropColumn(['ip_address']);
        });
    }
}
