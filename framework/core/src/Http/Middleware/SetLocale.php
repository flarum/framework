<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Locale\LocaleManager;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class SetLocale implements MiddlewareInterface
{
    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @param LocaleManager $locales
     */
    public function __construct(LocaleManager $locales)
    {
        $this->locales = $locales;
    }

    public function process(Request $request, DelegateInterface $delegate)
    {
        $actor = $request->getAttribute('actor');

        if ($actor->exists) {
            $locale = $actor->getPreference('locale');
        } else {
            $locale = array_get($request->getCookieParams(), 'locale');
        }

        if ($locale && $this->locales->hasLocale($locale)) {
            $this->locales->setLocale($locale);
        }

        return $delegate->process($request);
    }
}
