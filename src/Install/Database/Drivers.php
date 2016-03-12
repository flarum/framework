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
            "mysql" => array(
                "name" => "MySQL",
                "php_driver" => "pdo_mysql"
            ),
            "pgsql" => array(
                "name" => "PostgreSQL",
                "php_driver" => "pdo_pgsql"
            ),
        );

    }

    /**
     * @return array
     */
    public function getSupportedPhpDrivers()
    {
        return array_column($this->drivers, 'php_driver');
    }


    /**
     * @return array
     */
    public function getLoadedDatabaseDrivers()
    {

        $loadedDrivers = array();

        foreach($this->drivers as $driver => $values) {
            if(extension_loaded($values['php_driver'])) {
                $loadedDrivers[$driver] = $values['name'];
            }
        }

        return $loadedDrivers;
    }

    /**
     * @return array
     */
    public function getDrivers()
    {
        return $this->drivers;
    }
}
