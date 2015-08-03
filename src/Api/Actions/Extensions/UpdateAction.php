<?php namespace Flarum\Api\Actions\Extensions;

use Flarum\Api\Actions\JsonApiAction;
use Flarum\Api\Request;
use Illuminate\Contracts\Bus\Dispatcher;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Support\ExtensionManager;

class UpdateAction extends JsonApiAction
{
    protected $extensions;

    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    protected function respond(Request $request)
    {
        if (! $request->actor->isAdmin()) {
            throw new PermissionDeniedException;
        }

        $enabled = $request->get('enabled');
        $name = $request->get('name');

        if ($enabled === true) {
            $this->extensions->enable($name);
        } elseif ($enabled === false) {
            $this->extensions->disable($name);
        }

        app('flarum.formatter')->flush();

        $assetPath = public_path('assets');
        $manifest = file_get_contents($assetPath . '/rev-manifest.json');
        $revisions = json_decode($manifest, true);

        foreach ($revisions as $file => $revision) {
            @unlink($assetPath . '/' . substr_replace($file, '-' . $revision, strrpos($file, '.'), 0));
        }
    }
}
