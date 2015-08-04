<?php namespace Flarum\Api\Actions;

use Flarum\Api\Request;
use Flarum\Core\Settings\SettingsRepository;
use Flarum\Core\Groups\Permission;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Zend\Diactoros\Response\EmptyResponse;
use Exception;

class ConfigAction implements Action
{
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
     * {@inheritdoc}
     */
    public function handle(Request $request, array $routeParams = [])
    {
        if (! $request->actor->isAdmin()) {
            throw new PermissionDeniedException;
        }

        $config = $request->get('config', []);

        // TODO: throw HTTP status 400 or 422
        if (! is_array($config)) {
            throw new Exception;
        }

        foreach ($config as $k => $v) {
            $this->settings->set($k, $v);

            if (strpos($k, 'theme_') === 0) {
                $forum = app('Flarum\Forum\Actions\ClientAction');
                $forum->flushAssets();

                $admin = app('Flarum\Admin\Actions\ClientAction');
                $admin->flushAssets();
            }
        }

        return new EmptyResponse(204);
    }
}
