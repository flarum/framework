<?php namespace Flarum\Install\Console;

interface ProvidesData
{
    public function getDatabaseConfiguration();

    public function getAdminUser();
}
