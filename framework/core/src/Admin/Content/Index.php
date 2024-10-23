<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Admin\Content;

use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Application;
use Flarum\Frontend\Document;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;

class Index
{
    public function __construct(
        protected Factory $view,
        protected ExtensionManager $extensions,
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function __invoke(Document $document, Request $request): Document
    {
        $extensions = $this->extensions->getExtensions();
        $extensionsEnabled = json_decode($this->settings->get('extensions_enabled', '{}'), true);
        $csrfToken = $request->getAttribute('session')->token();

        $dbDriver = $document->payload['dbDriver'];
        $dbVersion = $document->payload['dbVersion'];
        $phpVersion = $document->payload['phpVersion'];
        $flarumVersion = Application::VERSION;

        $document->content = $this->view->make(
            'flarum.admin::frontend.content.admin',
            compact('extensions', 'extensionsEnabled', 'csrfToken', 'flarumVersion', 'phpVersion', 'dbVersion', 'dbDriver')
        );

        return $document;
    }
}
