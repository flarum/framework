<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Extension\ExtensionManager;
use Flarum\User\User;
use s9e\TextFormatter\Parser;

class CheckPermissions
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;
    
    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }
    
    public function __invoke(Parser $parser, $content, string $text, ?User $actor): string
    {
        // Check user has `mentionGroups` permission, if not, remove the `GROUPMENTION` tag from the parser.
        if ($actor && $actor->cannot('mentionGroups')) {
            $parser->disableTag('GROUPMENTION');
        }

        // Check `flarum/tags` is enabled and user has `mentionTags` permission, if not, remove the `TAGMENTION` tag from the parser.
        if ($this->extensions->isEnabled('flarum-tags') && $actor && $actor->cannot('mentionTags')) {
            $parser->disableTag('TAGMENTION');
        }

        return $text;
    }
}
