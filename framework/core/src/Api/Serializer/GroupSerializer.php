<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Group\Group;
use Flarum\Locale\TranslatorInterface;
use InvalidArgumentException;

class GroupSerializer extends AbstractSerializer
{
    protected $type = 'groups';

    public function __construct(
        protected TranslatorInterface $translator
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes(object|array $model): array
    {
        if (! ($model instanceof Group)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Group::class
            );
        }

        return [
            'nameSingular' => $this->translateGroupName($model->name_singular),
            'namePlural'   => $this->translateGroupName($model->name_plural),
            'color'        => $model->color,
            'icon'         => $model->icon,
            'isHidden'     => $model->is_hidden
        ];
    }

    private function translateGroupName(string $name): string
    {
        $translation = $this->translator->trans($key = 'core.group.'.strtolower($name));

        if ($translation !== $key) {
            return $translation;
        }

        return $name;
    }
}
