<?php

include('example.php');

class testbar {
  public function __construct($connection, $test){
    $this->bar = $bar;
    $this->test = $test;
  }
  
  public function serialize($stuff) {
    $this->test->assertEquals("woot", $stuff);
    return $this->bar->serialize($stuff);
  }
}

class exampleTest extends PHPUnit_Framework_TestCase {
  
  public function testWootResults() {
    $test = new foo(new woot(), new testbar(new bar(), $this));
    $this->assertEquals('woot serialized', $test->veryImportantStuff());
  }
}