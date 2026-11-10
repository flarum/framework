<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Tests\integration\api;

use Flarum\Gdpr\Data\Forum;
use Flarum\Gdpr\Data\Type;
use Flarum\Gdpr\DataProcessor;
use Flarum\Gdpr\Extend\UserData;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ExtenderTest extends TestCase
{
    #[Test]
    public function custom_data_type_can_be_added()
    {
        $this->extend(
            (new UserData())
                ->addType(MyNewDataType::class)
        );

        $this->app();

        $types = $this->getDataProcessor()->types();

        $this->assertArrayHasKey(MyNewDataType::class, $types);
    }

    #[Test]
    public function data_type_can_be_removed()
    {
        $this->extend(
            (new UserData())
                ->removeType(Forum::class)
        );

        $this->app();

        $types = $this->getDataProcessor()->types();

        $this->assertArrayNotHasKey(Forum::class, $types);
    }

    #[Test]
    public function custom_user_column_can_be_removed()
    {
        $this->extend(
            (new UserData())
                ->removeUserColumns('custom_column')
        );

        $this->app();

        $columns = $this->getDataProcessor()->removableUserColumns();

        $this->assertContains('custom_column', $columns);
    }

    #[Test]
    public function custom_user_columns_can_be_removed()
    {
        $this->extend(
            (new UserData())
                ->removeUserColumns(['custom_column', 'another_column'])
        );

        $this->app();

        $columns = $this->getDataProcessor()->removableUserColumns();

        $this->assertContains('custom_column', $columns);
        $this->assertContains('another_column', $columns);
    }

    #[Test]
    public function custom_user_columns_can_be_added()
    {
        $this->extend(
            (new UserData())
                ->removeUserColumns(['custom_column1', 'another_column'])
        );

        $this->app();

        $columns = $this->getDataProcessor()->removableUserColumns();

        $this->assertContains('custom_column1', $columns);
        $this->assertContains('another_column', $columns);
    }

    #[Test]
    public function custom_pii_key_can_be_registered_via_extender()
    {
        $this->extend(
            (new UserData())
                ->addPiiKeysForSerialization('custom_pii_field')
        );

        $this->app();

        $keys = $this->getDataProcessor()->getPiiKeysForSerialization();

        $this->assertContains('custom_pii_field', $keys);
    }

    #[Test]
    public function multiple_custom_pii_keys_can_be_registered_via_extender()
    {
        $this->extend(
            (new UserData())
                ->addPiiKeysForSerialization(['field_a', 'field_b'])
        );

        $this->app();

        $keys = $this->getDataProcessor()->getPiiKeysForSerialization();

        $this->assertContains('field_a', $keys);
        $this->assertContains('field_b', $keys);
    }

    #[Test]
    public function built_in_pii_fields_are_present_without_any_extender()
    {
        $this->app();

        $keys = $this->getDataProcessor()->getPiiKeysForSerialization();

        $this->assertContains('email', $keys);
        $this->assertContains('username', $keys);
        $this->assertContains('ip_address', $keys);
        $this->assertContains('last_ip_address', $keys);
    }

    #[Test]
    public function pii_fields_from_custom_data_type_are_included()
    {
        $this->extend(
            (new UserData())
                ->addType(DataTypeWithPii::class)
        );

        $this->app();

        $keys = $this->getDataProcessor()->getPiiKeysForSerialization();

        $this->assertContains('bio', $keys);
        $this->assertContains('location', $keys);
    }

    protected function getDataProcessor(): DataProcessor
    {
        return $this->app()->getContainer()->make(DataProcessor::class);
    }
}

class MyNewDataType
{
    public static function dataType(): string
    {
        return 'my-new-data-type';
    }
}

class DataTypeWithPii extends Type
{
    public static function piiFields(): array
    {
        return ['bio', 'location'];
    }

    public static function exportDescription(): string
    {
        return '';
    }

    public function export(): ?array
    {
        return null;
    }

    public static function anonymizeDescription(): string
    {
        return '';
    }

    public function anonymize(): void
    {
    }

    public static function deleteDescription(): string
    {
        return '';
    }

    public function delete(): void
    {
    }
}
