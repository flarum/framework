<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Driver;

use Flarum\Frontend\Document;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

interface TitleDriverInterface
{
    public function makeTitle(Document $document, ServerRequestInterface $request, TranslatorInterface $translator, array $forumApiDocument): string;
}
