<?php
/*
 * This file is part of Flarum.
 *
 * (c) Vincent Jousse <vincent@jousse.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Database;

class Drivers
{
    protected $drivers;

    public function __construct()
    {
        $this->drivers = array(
            "pdo_mysql" => "MySQL",
            "pdo_pgsql" => "PostgreSQL",
        );

        $this->phpDrivers = array_keys($this->drivers);
    }

    /**
     * @return array
     */
    public function getSupportedPhpDrivers()
    {
        return $this->phpDrivers;
    }


    /**
     * @return array
     */
    public function getLoadedDatabaseDrivers()
    {
        return array_filter(
            $this->drivers,
            function($driver) {
                return extension_loaded($driver);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @return array
     */
    public function getDrivers()
    {
        return $this->drivers;
    }
}
