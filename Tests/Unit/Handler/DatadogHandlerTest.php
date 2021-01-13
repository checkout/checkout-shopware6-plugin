<?php

use PHPUnit\Framework\TestCase;
use Checkoutcom\Handler\CloudEventsHandler;

class CloudEventsHandlerTest extends TestCase {

    public function testLogLevelName() {
        
        $name = CloudEventsHandler::logLevelName(100);
        $this->assertEquals($name, "debug");

    } 
}