<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Tests\integration\api;

use Flarum\Gdpr\Data\Forum;
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
