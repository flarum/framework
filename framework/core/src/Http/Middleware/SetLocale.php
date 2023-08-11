<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Closure;
use Flarum\Http\RequestUtil;
use Flarum\Locale\LocaleManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale implements IlluminateMiddlewareInterface
{
    public function __construct(
        protected LocaleManager $locales
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $actor = RequestUtil::getActor($request);

        if ($actor->exists) {
            $locale = $actor->getPreference('locale');
        } else {
            $locale = $request->cookie('locale');
        }

        if ($locale && $this->locales->hasLocale($locale)) {
            $this->locales->setLocale($locale);
        }

        $request->attributes->set('locale', $this->locales->getLocale());

        return $next($request);
    }
}
