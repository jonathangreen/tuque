<?php

namespace Islandora\Tuque\Tests;

use Islandora\Tuque\Connection\CurlConnection;
use PHPUnit_Framework_TestCase;

class HttpConnectionTest extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $this->xml = <<<foo
<woo>
  <test>
    <xml></xml>
  </test>
</woo>

foo;
    }

    function testGet()
    {
        $connection = new CurlConnection();
        $page = $connection->getRequest(TEST_XML_URL);
        $this->assertEquals($this->xml, $page['content']);
    }

    function testGetFile()
    {
        $connection = new CurlConnection();
        $file = tempnam(sys_get_temp_dir(), 'test');
        $page = $connection->getRequest(TEST_XML_URL, false, $file);
        $this->assertEquals($this->xml, file_get_contents($file));
        unlink($file);
    }
}
