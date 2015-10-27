<?php
namespace Tests\Test;

use Mockery;
use PHPUnit_Framework_TestCase;

class TestCase extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mockery::close();

        $this->init();
    }

    protected function init()
    {
        // To be overloaded by children - saves having to do setUp/mockery::close every time
    }
}
