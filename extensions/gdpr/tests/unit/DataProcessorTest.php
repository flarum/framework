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

        // Resetting all static state before each test
        DataProcessor::setTypes([
            Data\Forum::class => null,
            Data\Assets::class => null,
            Data\Posts::class => null,
            Data\Tokens::class => null,
            Data\Discussions::class => null,
            Data\User::class => null,
        ]);
        DataProcessor::resetRemovableUserColumns();
        DataProcessor::resetExtraPiiKeysForSerialization();
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

    #[Test]
    public function it_aggregates_pii_fields_from_registered_types()
    {
        $processor = new DataProcessor();

        $keys = $processor->getPiiKeysForSerialization();

        // Fields declared by built-in types
        $this->assertContains('email', $keys);           // Data\User
        $this->assertContains('username', $keys);        // Data\User
        $this->assertContains('last_seen_at', $keys);    // Data\User
        $this->assertContains('joined_at', $keys);       // Data\User
        $this->assertContains('preferences', $keys);     // Data\User
        $this->assertContains('ip_address', $keys);      // Data\Posts
        $this->assertContains('last_ip_address', $keys); // Data\Tokens
    }

    #[Test]
    public function it_merges_extra_pii_keys_with_type_pii_fields()
    {
        $processor = new DataProcessor();

        DataProcessor::addPiiKeysForSerialization(['custom_field']);

        $keys = $processor->getPiiKeysForSerialization();

        $this->assertContains('custom_field', $keys);
        $this->assertContains('email', $keys); // still includes type-sourced keys
    }

    #[Test]
    public function it_deduplicates_pii_keys()
    {
        $processor = new DataProcessor();

        // 'email' is already declared by Data\User::piiFields()
        DataProcessor::addPiiKeysForSerialization(['email', 'email', 'custom_field']);

        $keys = $processor->getPiiKeysForSerialization();

        $this->assertCount(1, array_filter($keys, fn ($k) => $k === 'email'));
        $this->assertCount(1, array_filter($keys, fn ($k) => $k === 'custom_field'));
    }

    #[Test]
    public function removing_a_type_removes_its_pii_fields()
    {
        $processor = new DataProcessor();

        DataProcessor::removeType(Data\Posts::class);

        $keys = $processor->getPiiKeysForSerialization();

        $this->assertNotContains('ip_address', $keys);
    }

    #[Test]
    public function types_with_no_pii_fields_contribute_nothing()
    {
        // Forum and Discussions declare no PII fields
        DataProcessor::setTypes([
            Data\Forum::class => null,
            Data\Discussions::class => null,
        ]);
        $processor = new DataProcessor();

        $keys = $processor->getPiiKeysForSerialization();

        $this->assertEmpty($keys);
    }

    #[Test]
    public function extra_pii_keys_are_included_even_when_no_types_declare_pii()
    {
        DataProcessor::setTypes([Data\Forum::class => null]);
        DataProcessor::addPiiKeysForSerialization(['my_field']);
        $processor = new DataProcessor();

        $keys = $processor->getPiiKeysForSerialization();

        $this->assertEquals(['my_field'], $keys);
    }

    #[Test]
    public function reset_clears_extra_pii_keys()
    {
        DataProcessor::addPiiKeysForSerialization(['my_field']);
        DataProcessor::resetExtraPiiKeysForSerialization();

        $processor = new DataProcessor();
        $keys = $processor->getPiiKeysForSerialization();

        $this->assertNotContains('my_field', $keys);
    }
}
