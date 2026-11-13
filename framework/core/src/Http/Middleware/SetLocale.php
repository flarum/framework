<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Http\RequestUtil;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class SetLocale implements Middleware
{
    public function __construct(
        protected LocaleManager $locales,
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $actor = RequestUtil::getActor($request);
        $locale = null;

        // With the language selector hidden, nobody can choose a language, so
        // the default applies to everyone. Earlier choices are kept, not
        // cleared, so they come back if the selector is shown again.
        if ($this->settings->get('show_language_selector', true)) {
            if ($actor->exists) {
                $locale = $actor->getPreference('locale');
            } else {
                $locale = Arr::get($request->getCookieParams(), 'locale');
            }
        }

        if ($locale && $this->locales->hasLocale($locale)) {
            $this->locales->setLocale($locale);
        }

        $request = $request->withAttribute('locale', $this->locales->getLocale());

        return $handler->handle($request);
    }
}
