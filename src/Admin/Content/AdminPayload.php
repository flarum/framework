<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Admin\Content;

use Flarum\Extension\ExtensionManager;
use Flarum\Frontend\Content\ContentInterface;
use Flarum\Frontend\FrontendView;
use Flarum\Group\Permission;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\ConnectionInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminPayload implements ContentInterface
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var ConnectionInterface
     */
    protected $db;

    /**
     * @param SettingsRepositoryInterface $settings
     * @param ExtensionManager $extensions
     * @param ConnectionInterface $db
     */
    public function __construct(SettingsRepositoryInterface $settings, ExtensionManager $extensions, ConnectionInterface $db)
    {
        $this->settings = $settings;
        $this->extensions = $extensions;
        $this->db = $db;
    }

    public function populate(FrontendView $view, Request $request)
    {
        $view->payload['settings'] = $this->settings->all();
        $view->payload['permissions'] = Permission::map();
        $view->payload['extensions'] = $this->extensions->getExtensions()->toArray();

        $view->payload['phpVersion'] = PHP_VERSION;
        $view->payload['mysqlVersion'] = $this->db->selectOne('select version() as version')->version;
    }
}
