<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Search;

use Flarum\Search\Filter\RangeValue;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class RangeValueTest extends TestCase
{
    public static function dates(): array
    {
        return [
            'closed' => ['2024-01-01..2024-12-31', ['2024-01-01', '2024-12-31']],
            'open end' => ['2024-01-01..', ['2024-01-01', null]],
            'open start' => ['..2024-12-31', [null, '2024-12-31']],
            'single value' => ['2024-02-29', ['2024-02-29', '2024-02-29']],
            'whitespace' => [' 2024-01-01 .. 2024-01-02 ', ['2024-01-01', '2024-01-02']],
            'both empty' => ['..', null],
            'empty' => ['', null],
            'impossible date' => ['2023-02-29..', null],
            'not a date' => ['today..', null],
            'datetime' => ['2024-01-01T00:00..', null],
            'number' => ['5..10', null],
            'three dots' => ['2024-01-01...2024-01-02', null],
        ];
    }

    #[Test]
    #[DataProvider('dates')]
    public function parses_date_ranges(string $value, ?array $expected)
    {
        $this->assertSame($expected, RangeValue::parse($value));
    }

    public static function numbers(): array
    {
        return [
            'closed' => ['1..10', ['1', '10']],
            'open start' => ['..10', [null, '10']],
            'decimals and negatives' => ['-1.5..2.5', ['-1.5', '2.5']],
            'single' => ['3', ['3', '3']],
            'not a number' => ['a..b', null],
            'overflowing to infinity' => ['1e999..', null],
            'INF' => ['..INF', null],
            'NAN' => ['NAN', null],
            'exponent within range' => ['1e3..', ['1e3', null]],
        ];
    }

    #[Test]
    #[DataProvider('numbers')]
    public function parses_number_ranges(string $value, ?array $expected)
    {
        $this->assertSame($expected, RangeValue::parse($value, 'number'));
    }
}
