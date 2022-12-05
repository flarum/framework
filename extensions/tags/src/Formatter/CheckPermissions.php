<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Formatter;

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
        // Check `flarum/mentions` is enabled and user has `mentionTags` permission, if not, remove the `TAGMENTION` tag from the parser.
        if ($this->extensions->isEnabled('flarum-mentions') && $actor && $actor->cannot('mentionTags')) {
            $parser->disableTag('TAGMENTION');
        }

        return $text;
    }
}
