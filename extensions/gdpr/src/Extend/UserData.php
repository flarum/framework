<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Gdpr\DataProcessor;
use Illuminate\Contracts\Container\Container;

class UserData implements ExtenderInterface
{
    protected array $types = [];
    protected array $removeTypes = [];
    protected array $removeUserColumns = [];
    protected array $piiKeysForSerialization = [];

    public function extend(Container $container, ?Extension $extension = null): void
    {
        foreach ($this->types as $type) {
            DataProcessor::addType($type, $extension?->getId());
        }

        foreach ($this->removeTypes as $type) {
            DataProcessor::removeType($type);
        }

        DataProcessor::removeUserColumns($this->removeUserColumns, $extension?->getId());
        DataProcessor::addPiiKeysForSerialization($this->piiKeysForSerialization, $extension?->getId());
    }

    /**
     * Register a new data type.
     *
     * Must be a class that implements Flarum\Gdpr\Contracts\DataType.
     *
     * @param string $type
     *
     * @return self
     */
    public function addType(string $type): self
    {
        $this->types[] = $type;

        return $this;
    }

    /**
     * Removes a data type from exports.
     *
     * @param string $type
     *
     * @return self
     */
    public function removeType(string $type): self
    {
        $this->removeTypes[] = $type;

        return $this;
    }

    /**
     * Removes one or multiple user table columns from exports.
     *
     * @param string|string[] $columns
     *
     * @return self
     */
    public function removeUserColumns($columns): self
    {
        $columns = (array) $columns;
        $this->removeUserColumns = array_merge($this->removeUserColumns, $columns);

        return $this;
    }

    /**
     * Register additional PII keys for serialization anonymization that are not covered by
     * any registered data type. Prefer implementing {@see \Flarum\Gdpr\Contracts\DataType::piiFields()}
     * on your data type class instead — that keeps the PII declaration co-located with the
     * anonymization logic. Use this method only for PII fields that don't belong to any type.
     *
     * @param string|string[] $keys
     *
     * @return self
     */
    public function addPiiKeysForSerialization($keys): self
    {
        $keys = (array) $keys;
        $this->piiKeysForSerialization = array_merge($this->piiKeysForSerialization, $keys);

        return $this;
    }
}
