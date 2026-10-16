<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\integration\api;

use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RetireMigrationTest extends TestCase
{
    #[Test]
    public function it_drops_the_previous_extension_managers_table_and_settings(): void
    {
        $this->extension('flarum-extension-manager');
        $this->app();

        $db = $this->database();
        $schema = $db->getSchemaBuilder();

        $schema->dropIfExists('extension_manager_tasks');
        $schema->create('extension_manager_tasks', function ($table) {
            $table->increments('id');
        });
        $db->table('settings')->insert([
            ['key' => 'flarum-extension-manager.queue_jobs', 'value' => '1'],
            ['key' => 'flarum-extension-manager.task_retention_days', 'value' => '7'],
            ['key' => 'flarum-extension-manager-unrelated', 'value' => 'kept'],
        ]);

        $migration = require __DIR__.'/../../../migrations/2026_10_17_000000_retire_the_previous_extension_manager.php';
        $migration['up']($schema);

        $this->assertFalse($schema->hasTable('extension_manager_tasks'));
        $this->assertSame(0, $db->table('settings')->where('key', 'like', 'flarum-extension-manager.%')->count());
        $this->assertSame('kept', $db->table('settings')->where('key', 'flarum-extension-manager-unrelated')->value('value'));
    }
}
