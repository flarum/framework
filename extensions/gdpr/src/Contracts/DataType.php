<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Contracts;

interface DataType
{
    public static function dataType(): string;

    /**
     * Description of what this data type contains.
     *
     * @return string
     */
    public static function exportDescription(): string;

    /**
     * Data to be added to the zipfile.
     *
     * @return array<string, string>|array<array<string, string>>|null
     *
     * - array<string, string>: Represents a single data set, e.g., ['filename.json' => '{...data...}']
     * - array<array<string, string>>: Represents multiple data sets, e.g., [['filename1.json' => '{...data1...}'], ['filename2.json' => '{...data2...}']]
     * - null: No data available for export.
     */
    public function export(): ?array;

    /**
     * Description of what happens to the data when it is anonymized.
     *
     * @return string
     */
    public static function anonymizeDescription(): string;

    /**
     * Logic to anonymize the data.
     *
     * @return void
     */
    public function anonymize(): void;

    /**
     * Description of what happens to the data when it is deleted.
     *
     * @return string
     */
    public static function deleteDescription(): string;

    /**
     * Logic to delete the data.
     *
     * @return void
     */
    public function delete(): void;
}
