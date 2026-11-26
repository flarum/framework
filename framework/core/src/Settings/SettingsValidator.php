<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

use Closure;
use Flarum\Forum\Branding\ShortName;
use Flarum\Forum\Branding\SplashScreen;
use Flarum\Forum\Branding\WebManifest;
use Flarum\Foundation\AbstractValidator;
use Flarum\Locale\TranslatorInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Validator;

class SettingsValidator extends AbstractValidator
{
    /**
     * These rules apply to all attributes.
     *
     * Entries in the default DB settings table are limited to 65,000
     * characters. We validate against this to avoid confusing errors.
     */
    protected array $globalRules = [
        'max:65000',
    ];

    protected array $rules = [
        'forum_short_name' => ['max:'.ShortName::MAX_LENGTH],
        'splash_screen_background' => ['nullable', 'regex:'.SplashScreen::BACKGROUND_PATTERN],
        'search_min_length' => ['required', 'integer', 'min:1'],
        'app_start_page' => ['nullable'],
    ];

    public function __construct(Factory $validator, TranslatorInterface $translator, protected Container $container)
    {
        parent::__construct($validator, $translator);
    }

    protected function makeValidator(array $attributes): Validator
    {
        // Apply global rules first.
        $rules = array_map(function () {
            return $this->globalRules;
        }, $attributes);

        // Apply attribute specific rules.
        foreach ($rules as $key => $value) {
            $rules[$key] = array_merge($value, $this->rules[$key] ?? []);
        }

        // Built only when it's saved: it looks at the forum's routes.
        if (array_key_exists('app_start_page', $rules)) {
            $rules['app_start_page'][] = function (string $attribute, mixed $value, Closure $fail) {
                if (! is_string($value) || ! $this->container->make(WebManifest::class)->isStartPage($value)) {
                    $fail('validation.in')->translate();
                }
            };
        }

        $validator = $this->validator->make($attributes, $rules, $this->getMessages());

        foreach ($this->configuration as $callable) {
            $callable($this, $validator);
        }

        return $validator;
    }
}
