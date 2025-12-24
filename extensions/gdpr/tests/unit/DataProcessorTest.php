<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\tests\unit;

use Flarum\Gdpr\Data;
use Flarum\Gdpr\DataProcessor;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DataProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Resetting the types and removeUserColumns properties before each test
        DataProcessor::setTypes([
            Data\Forum::class => null,
            Data\Assets::class => null,
            Data\Posts::class => null,
            Data\Tokens::class => null,
            Data\Discussions::class => null,
            Data\User::class => null,
        ]);
        DataProcessor::removeUserColumns([]);
    }

    #[Test]
    public function it_can_add_a_new_type()
    {
        // Given
        $newType = 'TestData\TypeExample';
        $processor = new DataProcessor();

        // When
        DataProcessor::addType($newType);

        // Then
        $this->assertArrayHasKey($newType, $processor->types());
    }

    #[Test]
    public function it_can_remove_a_type()
    {
        // Given
        $typeToRemove = Data\Tokens::class;
        $processor = new DataProcessor();

        // When
        DataProcessor::removeType($typeToRemove);

        // Then
        $this->assertNotContains($typeToRemove, $processor->types());
    }

    #[Test]
    public function it_can_set_types()
    {
        // Given
        $newTypes = ['TestData\TypeA', 'TestData\TypeB'];
        $processor = new DataProcessor();

        // When
        DataProcessor::setTypes($newTypes);

        // Then
        $this->assertEquals($newTypes, $processor->types());
    }

    #[Test]
    public function it_can_add_removable_user_columns()
    {
        // Given
        $newColumns = ['columnA', 'columnB'];
        $processor = new DataProcessor();

        // When
        DataProcessor::removeUserColumns($newColumns);

        // Then
        $this->assertEquals($newColumns, $processor->removableUserColumns());
    }

    #[Test]
    public function user_class_is_always_the_last_entry()
    {
        // Given
        $newType = 'TestData\TypeExample';
        $processor = new DataProcessor();

        // When
        DataProcessor::addType($newType);

        // Then
        $types = $processor->types();
        $this->assertEquals(Data\User::class, array_key_last($types));
    }
}
