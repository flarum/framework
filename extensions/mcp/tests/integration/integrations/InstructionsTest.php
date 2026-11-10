<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\integrations;

use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Lock, sticky, mentions, suspend and approval only add instructions.
 */
class InstructionsTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-lock', 'flarum-sticky', 'flarum-mentions', 'flarum-suspend', 'flarum-approval');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    #[Test]
    public function integrations_explain_themselves_to_the_actor(): void
    {
        $admin = $this->instructions(1);

        // Each integration's notes are attributed to the extension it integrates.
        $this->assertStringContainsString('{"isLocked": true}', $admin['flarum-lock']);
        $this->assertStringContainsString('{"isSticky": true}', $admin['flarum-sticky']);
        $this->assertStringContainsString('@"Display Name"#p456', $admin['flarum-mentions']);
        $this->assertStringContainsString('suspendedUntil', $admin['flarum-suspend']);
        $this->assertStringContainsString('{"isApproved": true}', $admin['flarum-approval']);
        $this->assertNull($admin['flarum-mcp']);

        // Members cannot suspend, so they are not told how.
        $this->assertStringNotContainsString('suspendedUntil', (string) $this->instructions(2)['flarum-suspend']);
    }

    /**
     * @return array<string, ?string> extension ID => instructions
     */
    protected function instructions(int $userId): array
    {
        $overview = json_decode($this->rpcResult($this->modern(
            'resources/read',
            ['uri' => 'flarum://forum'],
            $this->bearer($this->developerToken($userId)),
            'flarum://forum'
        ))['contents'][0]['text'], true);

        return array_column($overview['extensions'], 'instructions', 'id');
    }
}
