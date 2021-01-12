<?php

use PHPUnit\Framework\TestCase;
use Checkoutcom\Handler\DatadogHandler;

class DatadogHandlerTest extends TestCase {

    public function testLogLevelName() {
        
        $name = DatadogHandler::logLevelName(100);
        $this->assertEquals($name, "debug");

    } 
}