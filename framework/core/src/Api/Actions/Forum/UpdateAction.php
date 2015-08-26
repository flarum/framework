<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Actions\Forum;

use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Core\Settings\SettingsRepository;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class UpdateAction extends SerializeResourceAction
{
    /**
     * @inheritdoc
     */
    public $serializer = 'Flarum\Api\Serializers\ForumSerializer';

    /**
     * @var SettingsRepository
     */
    protected $settings;

    /**
     * @param SettingsRepository $settings
     */
    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get the forum, ready to be serialized and assigned to the JsonApi
     * response.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Flarum\Core\Forum
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        if (! $request->actor->isAdmin()) {
            throw new PermissionDeniedException;
        }

        $config = $request->get('data.attributes.config');

        if (is_array($config)) {
            foreach ($config as $k => $v) {
                $this->settings->set($k, $v);
            }
        }

        return app('flarum.forum');
    }
}
