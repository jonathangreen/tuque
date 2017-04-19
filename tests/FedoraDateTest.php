<?php

namespace Islandora\Tuque\Tests;

use Islandora\Tuque\Date\FedoraDate;
use \PHPUnit\Framework\TestCase;

class FedoraDateTest extends TestCase
{

    function testToString()
    {
        $date = new FedoraDate("2012-03-13T19:15:07.529Z");
        $this->assertEquals("2012-03-13T19:15:07.529Z", (string)$date);
    }

    function testDateTimeSubclass()
    {
        $date = new FedoraDate("2012-03-13T19:15:07.529Z");
        $this->assertTrue(is_subclass_of($date, "DateTime"));
    }

    function testSerializeDate()
    {
        $date = new FedoraDate("2012-03-13T19:15:07.529Z");
        $serialized = serialize($date);
        $serialized_date = unserialize($serialized);
        $this->assertEquals((string) $date, (string) $serialized_date);
    }
}
