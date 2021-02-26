<?php

use PHPUnit\Framework\TestCase;
use Checkoutcom\Helper\Utilities;

class UtilitiesTest extends TestCase
{
    /**
     * Testing function isValidResponse
     */
    public function testIsValidResponseTrue()
    {
        $isValid = Utilities::isValidResponse(200);
        
        $this->assertTrue($isValid);
    }

    public function testIsValidResponseFalse()
    {
        $isValid = Utilities::isValidResponse(400);
        
        $this->assertFalse($isValid);
    }

    public function testFixAmount()
    {
        $amount = 10.00;
        $currency = 'EUR';
        $fixAmout = Utilities::fixAmount($amount, $currency);
        
        $this->assertSame($fixAmout, 1000);
    }

    public function testFixAmountReverse()
    {
        $amount = 1000;
        $currency = 'EUR';
        $fixAmout = Utilities::fixAmount($amount, $currency, true);
        
        $this->assertSame($fixAmout, 10.00);
    }
}