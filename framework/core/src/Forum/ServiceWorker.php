<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Http\UrlGenerator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory;
use Psr\Log\LoggerInterface;

class ServiceWorker
{
    public function __construct(
        protected Container $container,
        protected Factory $view,
        protected UrlGenerator $url,
        protected LoggerInterface $logger
    ) {
    }

    public function script(): string
    {
        $options = [
            'url' => $this->offlinePath(),
            'version' => substr(md5($this->offlinePage()), 0, 8),
        ];

        $script = file_get_contents(__DIR__.'/../../js/serviceWorker/offlinePage.js')
            .'offlinePage('.json_encode($options, JSON_UNESCAPED_SLASHES).");\n";

        foreach ($this->container->make('flarum.forum.service_worker') as $part) {
            if (! is_file($part['file'])) {
                $this->logger->warning("Service worker script {$part['file']} does not exist.");

                continue;
            }

            $script .= "\n// ".($part['owner'] ?? 'Added without an extension')."\n";
            $script .= "try {\n(function () {\n"
                .file_get_contents($part['file'])
                ."\n})();\n} catch (error) {\nconsole.error(error);\n}\n";
        }

        return $script;
    }

    public function offlinePage(): string
    {
        return $this->view->make('flarum.forum::offline')
            ->with('retryUrl', $this->offlinePath())
            ->render();
    }

    protected function offlinePath(): string
    {
        return (string) parse_url($this->url->to('forum')->route('offline'), PHP_URL_PATH);
    }
}
