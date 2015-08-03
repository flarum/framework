<?php namespace Flarum\Admin\Actions;

use Psr\Http\Message\ServerRequestInterface as Request;
use Flarum\Core\Settings\SettingsRepository;
use Flarum\Support\Action;
use Flarum\Core\Groups\Permission;
use Exception;

class UpdateConfigAction extends Action
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
        $config = array_get($request->getAttributes(), 'config', []);

        // TODO: throw HTTP status 400 or 422
        if (! is_array($config)) {
            throw new Exception;
        }

        foreach ($config as $k => $v) {
            $this->settings->set($k, $v);
        }

        $assetPath = public_path('assets');
        $manifest = file_get_contents($assetPath . '/rev-manifest.json');
        $revisions = json_decode($manifest, true);

        foreach ($revisions as $file => $revision) {
            @unlink($assetPath . '/' . substr_replace($file, '-' . $revision, strrpos($file, '.'), 0));
        }

        return $this->success();
    }
}
